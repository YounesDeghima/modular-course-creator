<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\AiJobSnapshot;
use App\Models\block;
use App\Models\chapter;
use App\Models\course;
use App\Models\lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ProcessPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 0;
    public int $tries   = 1;

    /**
     * @param int         $aiJobId
     * @param int|null    $recutSnapshotId  If set → skip MinerU, recut Ollama on this snapshot
     * @param string|null $recutModel       Model to use for recut (overrides job model)
     */
    public function __construct(
        public int     $aiJobId,
        public ?int    $recutSnapshotId = null,
        public ?string $recutModel      = null,
    ) {}

    public function handle(): void
    {
        $aiJob = AiJob::findOrFail($this->aiJobId);

        if ($aiJob->status === 'cancelled') {
            return;
        }

        $model = $this->recutModel ?? $aiJob->model ?? 'phi4';

        // For a pure Ollama recut we don't count it as a new "attempt"
        $isRecut = $this->recutSnapshotId !== null;
        $attempt = $isRecut ? ($aiJob->attempt ?? 1) : ($aiJob->attempt ?? 0) + 1;

        $aiJob->update([
            'status'     => 'processing',
            'attempt'    => $attempt,
            'started_at' => now(),
        ]);

        $modeLabel = $isRecut ? 'RECUT' : "Attempt {$attempt}/{$aiJob->max_attempts}";
        $aiJob->log("═══ {$modeLabel} | model={$model} ═══", 'info');

        $startTime = microtime(true);

        try {
            // ─────────────────────────────────────────────────────────────────
            // RECUT MODE: skip MinerU, re-run Ollama split on existing snapshot
            // ─────────────────────────────────────────────────────────────────
            if ($this->recutSnapshotId !== null) {
                $snapshot = AiJobSnapshot::findOrFail($this->recutSnapshotId);
                $aiJob->log("RECUT mode → snapshot #{$snapshot->md_index} (id={$snapshot->id})", 'info');
                $this->runSplitOnSnapshot($aiJob, $snapshot, $model, $startTime);
                return;
            }

            // ─────────────────────────────────────────────────────────────────
            // FULL MODE: MinerU → Ollama split → explode blocks
            // ─────────────────────────────────────────────────────────────────

            $mdIndex       = AiJobSnapshot::where('ai_job_id', $aiJob->id)->count() + 1;
            $imagesRelPath = "ai_images/{$aiJob->id}/{$mdIndex}";
            $imagesAbsPath = Storage::disk('public')->path($imagesRelPath);

            $aiJob->log("STEP 1 — Starting MinerU PDF extraction (md #{$mdIndex}).", 'info');

            $snapshot = AiJobSnapshot::create([
                'ai_job_id'     => $aiJob->id,
                'md_index'      => $mdIndex,
                'markdown'      => null,
                'images_path'   => $imagesRelPath,
                'image_urls'    => [],
                'md_status'     => 'processing',
                'md_error'      => null,
                'md_created_at' => now(),
                'results'       => [],
            ]);

            try {
                ['markdown' => $markdown, 'imageUrls' => $imageUrls]
                    = $this->extractWithMinerU($aiJob, $imagesAbsPath);

                $snapshot->update([
                    'markdown'   => $markdown,
                    'image_urls' => $imageUrls,
                    'md_status'  => 'done',
                ]);

                $chars = mb_strlen($markdown);
                $aiJob->log("STEP 1 — Done. Extracted {$chars} chars + " . count($imageUrls) . " images.", 'ok');

            } catch (\Throwable $e) {
                $snapshot->update(['md_status' => 'failed', 'md_error' => $e->getMessage()]);
                throw $e;
            }

            // STEP 2 + 3
            $this->runSplitOnSnapshot($aiJob, $snapshot, $model, $startTime);

        } catch (\Throwable $e) {
            $duration = (int)(microtime(true) - $startTime);
            $aiJob->log('FAILED — ' . $e->getMessage(), 'error');
            $aiJob->log('At: ' . basename($e->getFile()) . ':' . $e->getLine(), 'error');

            // Recut failures are not auto-retried (they are manual operations)
            $willRetry = !$isRecut && ($attempt < ($aiJob->max_attempts ?? 3));

            $aiJob->update([
                'status'           => 'failed',
                'error_message'    => $e->getMessage(),
                'finished_at'      => now(),
                'duration_seconds' => $duration,
            ]);

            if ($willRetry) {
                $delay = min(30 * $attempt, 120);
                $aiJob->log("Will auto-retry in {$delay}s (attempt {$attempt}/{$aiJob->max_attempts}).", 'warn');
                self::dispatch($aiJob->id)->delay(now()->addSeconds($delay));
            } else {
                $aiJob->log($isRecut ? "Recut failed — retry it manually." : "Max attempts ({$aiJob->max_attempts}) reached.", 'error');
            }
        }
    }

    // ── STEP 2: Ask Ollama to split markdown into chapters/lessons only ────────
    private function runSplitOnSnapshot(
        AiJob         $aiJob,
        AiJobSnapshot $snapshot,
        string        $model,
        float         $startTime
    ): void {
        $aiJob->log("STEP 2 — Asking Ollama [{$model}] to split into chapters/lessons…", 'info');

        $ollamaStart = microtime(true);
        $maxRetries  = 3;
        $lastError   = '';
        $splitData   = null;

        for ($try = 1; $try <= $maxRetries; $try++) {
            try {
                $aiJob->log("STEP 2 — attempt {$try}/{$maxRetries}…", 'info');
                $structure = $this->splitWithOllama($aiJob, $snapshot, $model);

                if (empty($structure['chapters'])) {
                    throw new \RuntimeException('Ollama returned no chapters.');
                }

                // Slice actual markdown content from the original snapshot text
                $splitData = $this->sliceMarkdownByBoundaries($snapshot->markdown ?? '', $structure);

                // PHP safety net: merge any lesson that is suspiciously short
                $splitData = $this->mergeShortLessons($aiJob, $splitData);

                $ch = count($splitData['chapters']);
                $ls = array_sum(array_map(fn($c) => count($c['lessons'] ?? []), $splitData['chapters']));
                $aiJob->log("STEP 2 — OK. Chapters:{$ch} Lessons:{$ls}.", 'ok');
                break;

            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $aiJob->log("STEP 2 — attempt {$try} failed: {$lastError}", 'warn');
                if ($try < $maxRetries) {
                    $aiJob->log("STEP 2 — Retrying in 5s…", 'warn');
                    sleep(5);
                }
            }
        }

        $ollamaDuration = (int)(microtime(true) - $ollamaStart);

        if ($splitData === null) {
            $aiJob->log("STEP 2 — All Ollama retries exhausted: {$lastError}", 'error');
            $snapshot->addResult($model, 'failed', null, $lastError, $ollamaDuration);
            $aiJob->update([
                'status'           => 'failed',
                'error_message'    => "Ollama split failed on snapshot #{$snapshot->md_index}: {$lastError}",
                'finished_at'      => now(),
                'duration_seconds' => (int)(microtime(true) - $startTime),
            ]);
            return;
        }

        // Save the split JSON to the snapshot result
        $splitJson = json_encode($splitData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $snapshot->addResult($model, 'done', $splitJson, null, $ollamaDuration);

        $aiJob->update([
            'result_json' => $splitJson,
        ]);

        // STEP 3: explode blocks for each lesson
        $this->explodeAllLessons($aiJob, $splitData, $snapshot->image_urls ?? [], $startTime, $model);
    }

    // ── STEP 3: Build course/chapters/lessons/blocks from split data ───────────
    private function explodeAllLessons(
        AiJob  $aiJob,
        array  $splitData,
        array  $imageUrls,
        float  $startTime,
        string $model = 'phi4'
    ): void {
        $aiJob->log("STEP 3 — Creating course structure and exploding lesson blocks…", 'info');

        DB::beginTransaction();
        try {
            $t = fn($s, $max = 255) => mb_substr((string)($s ?? ''), 0, $max);

            $courseRecord = course::create([
                'title'       => $t($splitData['title'] ?? 'Untitled Course'),
                'year'        => $splitData['year']   ?? $aiJob->year,
                'branch'      => $splitData['branch'] ?? $aiJob->branch,
                'description' => $t($splitData['description'] ?? '', 1000),
                'status'      => 'draft',
            ]);

            $aiJob->log("STEP 3 — Course created (id={$courseRecord->id}).", 'info');

            $lessonTotal  = 0;
            $lessonFailed = 0;

            foreach (($splitData['chapters'] ?? []) as $chIdx => $chData) {
                $chapterRecord = chapter::create([
                    'course_id'      => $courseRecord->id,
                    'title'          => $t($chData['title'] ?? 'Chapter ' . ($chIdx + 1)),
                    'description'    => $t($chData['description'] ?? '', 1000),
                    'chapter_number' => $chData['chapter_number'] ?? ($chIdx + 1),
                    'status'         => 'draft',
                ]);

                foreach (($chData['lessons'] ?? []) as $lIdx => $lData) {
                    $lessonTotal++;
                    $lessonNum    = $lData['lesson_number'] ?? ($lIdx + 1);
                    $lessonTitle  = $t($lData['title'] ?? 'Lesson ' . $lessonNum);
                    $lessonMd     = $lData['markdown'] ?? '';

                    $lessonRecord = lesson::create([
                        'chapter_id'    => $chapterRecord->id,
                        'title'         => $lessonTitle,
                        'description'   => $t($lData['description'] ?? '', 1000),
                        'lesson_number' => $lessonNum,
                        'content'       => '',
                        'status'        => 'draft',
                    ]);

                    // Inject image URLs into markdown so the parser can pick them up
                    $mdWithImages = $this->injectImageUrls($lessonMd, $imageUrls);

                    try {
                        $segments = $this->parseMarkdownToSegments($mdWithImages);

                        if (empty($segments)) {
                            // Nothing parsed — save as raw markdown block
                            $this->saveRawMarkdownBlock($lessonRecord->id, $lessonMd, 1);
                            $aiJob->log("STEP 3 — Lesson \"{$lessonTitle}\": empty segments, saved as raw markdown block.", 'warn');
                        } else {
                            foreach ($segments as $bIdx => $seg) {
                                $blk = block::create([
                                    'lesson_id'    => $lessonRecord->id,
                                    'type'         => $seg['type'],
                                    'content'      => $seg['content'],
                                    'block_number' => $bIdx + 1,
                                ]);
                                if ($seg['type'] === 'exercise') {
                                    $blk->solutions()->create(['solution_number' => 1, 'content' => 'nothing here yet']);
                                }
                            }
                            $aiJob->log("STEP 3 — Lesson \"{$lessonTitle}\": " . count($segments) . " blocks created.", 'ok');

                            // Generate exercises for this lesson
                            $nextBlockNum = count($segments) + 1;
                            try {
                                $exercises = $this->generateExercises($aiJob, $lessonTitle, $lessonMd, $model);
                                foreach ($exercises as $ex) {
                                    $blk = block::create([
                                        'lesson_id'    => $lessonRecord->id,
                                        'type'         => 'exercise',
                                        'content'      => $ex['question'],
                                        'block_number' => $nextBlockNum++,
                                    ]);
                                    $blk->solutions()->create([
                                        'solution_number' => 1,
                                        'content'         => $ex['answer'],
                                    ]);
                                }
                                $aiJob->log("STEP 3 — Lesson \"{$lessonTitle}\": " . count($exercises) . " exercise(s) appended.", 'ok');
                            } catch (\Throwable $e) {
                                $aiJob->log("STEP 3 — Lesson \"{$lessonTitle}\" exercise generation failed: " . $e->getMessage() . " — skipping exercises.", 'warn');
                            }
                        }

                    } catch (\Throwable $e) {
                        $lessonFailed++;
                        $aiJob->log("STEP 3 — Lesson \"{$lessonTitle}\" explode FAILED: " . $e->getMessage() . " — saved raw markdown block.", 'error');
                        // Save raw markdown so teacher can manually explode
                        $this->saveRawMarkdownBlock($lessonRecord->id, $lessonMd, 1);
                    }
                }
            }

            $aiJob->update(['status' => 'done', 'finished_at' => now(), 'duration_seconds' => (int)(microtime(true) - $startTime), 'error_message' => null]);
            DB::commit();

            $summary = "STEP 3 — Done. Lessons:{$lessonTotal}" . ($lessonFailed > 0 ? " ({$lessonFailed} saved as raw markdown — explode manually)." : " all exploded OK.");
            $aiJob->log($summary, $lessonFailed > 0 ? 'warn' : 'ok');
            $aiJob->log("Course id={$courseRecord->id} created as draft.", 'ok');

        } catch (\Throwable $e) {
            DB::rollBack();
            $aiJob->log("STEP 3 — Course creation transaction failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    // ── Save a single raw markdown block (fallback for failed lessons) ─────────
    private function saveRawMarkdownBlock(int $lessonId, string $markdown, int $blockNumber): void
    {
        block::create([
            'lesson_id'    => $lessonId,
            'type'         => 'markdown',
            'content'      => $markdown ?: '<!-- empty lesson -->',
            'block_number' => $blockNumber,
        ]);
    }

    // ── Generate 2-3 exercises for a lesson via a focused Ollama call ─────────
    private function generateExercises(AiJob $aiJob, string $lessonTitle, string $lessonMd, string $model): array
    {
        // Cap what we send — exercises don't need the full markdown
        $maxChars  = 6000;
        $content   = mb_strlen($lessonMd) > $maxChars
            ? mb_substr($lessonMd, 0, $maxChars) . "\n[...truncated...]"
            : $lessonMd;

        $prompt = <<<PROMPT
You are a teacher creating exercises for a lesson titled "{$lessonTitle}".

Read the lesson content below and write exactly 3 exercises that test understanding of the key concepts.
Each exercise should be a clear question or task. Include a concise model answer for each.

OUTPUT FORMAT (pure JSON, no markdown fences, no extra text):
{
  "exercises": [
    { "question": "...", "answer": "..." },
    { "question": "...", "answer": "..." },
    { "question": "...", "answer": "..." }
  ]
}

RULES:
- Base exercises strictly on the lesson content — do not invent topics not covered.
- Mix question types: comprehension, application, and reflection.
- Questions should be specific, not vague ("What is X?" is fine; "Explain everything" is not).
- Answers should be concise model answers a student can compare against.

--- LESSON CONTENT ---
{$content}
--- END ---
PROMPT;

        $response = Http::timeout(120)
            ->withOptions(['connect_timeout' => 10])
            ->post('http://localhost:11434/api/generate', [
                'model'   => $model,
                'prompt'  => $prompt,
                'stream'  => false,
                'format'  => 'json',
                'options' => [
                    'temperature' => 0.3,
                    'num_predict' => 1024,
                    'num_ctx'     => 8192,
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException("Ollama HTTP error on exercise generation: " . $response->status());
        }

        $jsonString = $response->json('response') ?? '';
        if (empty($jsonString)) {
            throw new \RuntimeException('Ollama returned empty response for exercises.');
        }

        // Strip accidental markdown fences
        $jsonString = preg_replace('/^```json\s*/i', '', trim($jsonString));
        $jsonString = preg_replace('/^```\s*/i',     '', $jsonString);
        $jsonString = preg_replace('/```\s*$/',       '', $jsonString);

        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($decoded['exercises'])) {
            throw new \RuntimeException('Exercise JSON invalid or empty: ' . json_last_error_msg());
        }

        return array_slice($decoded['exercises'], 0, 3); // max 3
    }

    // ── Ollama: boundaries only — model returns heading markers, NOT content ──
    private function splitWithOllama(AiJob $aiJob, AiJobSnapshot $snapshot, string $model): array
    {
        $markdown  = $snapshot->markdown ?? '';
        $maxChars  = 60000;
        $truncated = mb_strlen($markdown) > $maxChars
            ? mb_substr($markdown, 0, $maxChars) . "\n\n[...truncated...]"
            : $markdown;

        $origLen = mb_strlen($markdown);
        $sentLen = mb_strlen($truncated);
        $aiJob->log("Markdown {$origLen} chars → sending {$sentLen} chars to [{$model}].", 'info');

        $year   = $aiJob->year;
        $branch = $aiJob->branch;

        $prompt = <<<PROMPT
You are a course structure analyzer. Read the markdown document below and identify where chapters and lessons begin.

YOUR ONLY JOB: return a lightweight JSON structure with chapter/lesson metadata and the EXACT first line of text that starts each lesson (so it can be located in the original document).
Do NOT copy or reproduce any lesson content. Do NOT paraphrase content. Only titles, descriptions, and boundary markers.

OUTPUT FORMAT (pure JSON, no markdown fences, no extra text):
{
  "title": "<course title from first heading>",
  "year": $year,
  "branch": "$branch",
  "description": "<one sentence summary of the whole document>",
  "chapters": [
    {
      "title": "Chapter title",
      "description": "One sentence about this chapter.",
      "chapter_number": 1,
      "lessons": [
        {
          "title": "Lesson title",
          "description": "One sentence about this lesson.",
          "lesson_number": 1,
          "start_marker": "<copy the EXACT first line or heading that opens this lesson, verbatim from the document>"
        }
      ]
    }
  ]
}

LESSON SIZING RULES — very important:
- A lesson must be a substantial, self-contained learning unit. It should cover a full topic, not just a single heading.
- Do NOT create a new lesson for every ## heading. Group related headings together if they form one coherent topic.
- A lesson that would contain fewer than 4 paragraphs of content is too short — merge it with the next lesson instead.
- Prefer fewer, richer lessons over many thin ones. Aim for lessons a student could spend 10–15 minutes reading.
- If the whole document has little structure, use 1 chapter and 1–2 lessons max.

CHAPTER RULES:
- A new chapter only when there is a clear major topic shift (typically a # heading or equivalent).
- Do not create chapters for minor sub-sections.

--- MARKDOWN DOCUMENT ---
$truncated
--- END OF DOCUMENT ---
PROMPT;

        $aiJob->log("POST → http://localhost:11434/api/generate ({$model}, stream=false)…", 'info');

        $response = Http::timeout(0)
            ->withOptions(['connect_timeout' => 10])
            ->post('http://localhost:11434/api/generate', [
                'model'   => $model,
                'prompt'  => $prompt,
                'stream'  => false,
                'format'  => 'json',
                'options' => [
                    'temperature' => 0,
                    'num_predict' => 4096,   // boundaries JSON is tiny — cap it
                    'num_ctx'     => 16384,
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException("Ollama HTTP error: " . $response->status());
        }

        $jsonString = $response->json('response') ?? '';

        if (empty($jsonString)) {
            throw new \RuntimeException('Ollama returned an empty response.');
        }

        $aiJob->log('Response length: ' . mb_strlen($jsonString) . ' chars.', 'info');

        // Strip accidental markdown fences
        $jsonString = preg_replace('/^```json\s*/i', '', trim($jsonString));
        $jsonString = preg_replace('/^```\s*/i',     '', $jsonString);
        $jsonString = preg_replace('/```\s*$/',       '', $jsonString);

        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $snippet = mb_substr($jsonString, 0, 500);
            $aiJob->log('JSON parse error: ' . json_last_error_msg(), 'error');
            $aiJob->log('Snippet: ' . $snippet, 'error');
            throw new \RuntimeException('Ollama output is not valid JSON: ' . json_last_error_msg());
        }

        if (empty($decoded['chapters'])) {
            throw new \RuntimeException('Parsed JSON has no chapters.');
        }

        $aiJob->log('JSON parsed OK.', 'ok');
        return $decoded;
    }

    // ── Slice the original markdown into lessons using start_marker boundaries ─
    private function sliceMarkdownByBoundaries(string $fullMarkdown, array $structure): array
    {
        $lines = explode("\n", $fullMarkdown);

        // Collect all lessons in order with their markers
        $allLessons = [];
        foreach ($structure['chapters'] as $chIdx => $chapter) {
            foreach ($chapter['lessons'] ?? [] as $lIdx => $lesson) {
                $allLessons[] = [
                    'chIdx'  => $chIdx,
                    'lIdx'   => $lIdx,
                    'marker' => trim($lesson['start_marker'] ?? ''),
                    'title'  => $lesson['title'] ?? '',
                    'desc'   => $lesson['description'] ?? '',
                    'num'    => $lesson['lesson_number'] ?? ($lIdx + 1),
                ];
            }
        }

        if (empty($allLessons)) {
            // Fallback: whole doc is one lesson in one chapter
            $structure['chapters'][0]['lessons'][0]['markdown'] = $fullMarkdown;
            return $structure;
        }

        // Find the line index of each marker in the original document
        foreach ($allLessons as &$ls) {
            $ls['line'] = null;
            if ($ls['marker'] === '') continue;
            foreach ($lines as $lineIdx => $line) {
                if (trim($line) === $ls['marker'] || str_contains($line, $ls['marker'])) {
                    $ls['line'] = $lineIdx;
                    break;
                }
            }
        }
        unset($ls);

        // Sort by found line position; lessons whose marker wasn't found go to end
        usort($allLessons, fn($a, $b) => ($a['line'] ?? PHP_INT_MAX) <=> ($b['line'] ?? PHP_INT_MAX));

        // If first lesson marker not found at line 0, start it from line 0
        if ($allLessons[0]['line'] === null || $allLessons[0]['line'] > 0) {
            $allLessons[0]['line'] = 0;
        }

        // Slice content between consecutive start lines
        $total = count($lines);
        foreach ($allLessons as $i => &$ls) {
            $start = $ls['line'] ?? 0;
            $end   = $allLessons[$i + 1]['line'] ?? $total;
            $ls['markdown'] = implode("\n", array_slice($lines, $start, $end - $start));
        }
        unset($ls);

        // Rebuild the structure with actual markdown content
        $result = $structure;
        foreach ($result['chapters'] as &$chapter) {
            foreach ($chapter['lessons'] as &$lesson) {
                // Find matching lesson in allLessons by title
                foreach ($allLessons as $ls) {
                    if ($ls['title'] === $lesson['title']) {
                        $lesson['markdown'] = $ls['markdown'];
                        break;
                    }
                }
                $lesson['markdown'] ??= '';
            }
            unset($lesson);
        }
        unset($chapter);

        return $result;
    }

    // ── PHP safety net: merge lessons that are suspiciously short ─────────────
    private function mergeShortLessons(AiJob $aiJob, array $structure, int $minChars = 500): array
    {
        foreach ($structure['chapters'] as &$chapter) {
            $lessons = $chapter['lessons'] ?? [];
            $merged  = [];

            foreach ($lessons as $lesson) {
                $len = mb_strlen($lesson['markdown'] ?? '');

                if (!empty($merged) && $len < $minChars) {
                    // Absorb into the previous lesson
                    $prev = array_pop($merged);
                    $aiJob->log(
                        "Safety net — merged short lesson \"{$lesson['title']}\" ({$len} chars) into \"{$prev['title']}\".",
                        'warn'
                    );
                    $prev['markdown'] .= "\n\n" . ($lesson['markdown'] ?? '');
                    $prev['description'] .= ' ' . ($lesson['description'] ?? '');
                    $merged[] = $prev;
                } else {
                    $merged[] = $lesson;
                }
            }

            // Re-number
            foreach ($merged as $i => &$l) {
                $l['lesson_number'] = $i + 1;
            }
            unset($l);

            $chapter['lessons'] = $merged;
        }
        unset($chapter);

        return $structure;
    }

    // ── Inject image URLs as markdown references so the parser finds them ──────
    private function injectImageUrls(string $markdown, array $imageUrls): string
    {
        if (empty($imageUrls)) return $markdown;

        // Only inject images that aren't already referenced
        $appendix = '';
        foreach ($imageUrls as $url) {
            if (!str_contains($markdown, $url)) {
                $appendix .= "\n![image]({$url})";
            }
        }
        return $markdown . $appendix;
    }

    // ── Markdown → typed segments (copied from blockcontroller, standalone) ─────
    private function parseMarkdownToSegments(string $raw): array
    {
        $lines    = explode("\n", $raw);
        $segments = [];
        $i        = 0;
        $total    = count($lines);

        while ($i < $total) {
            $line    = $lines[$i];
            $trimmed = rtrim($line);

            // Fenced code block
            if (preg_match('/^```/', $trimmed)) {
                $code = '';
                $i++;
                while ($i < $total && !preg_match('/^```/', rtrim($lines[$i]))) {
                    $code .= $lines[$i] . "\n";
                    $i++;
                }
                $i++;
                if (trim($code) !== '') {
                    $segments[] = ['type' => 'code', 'content' => rtrim($code)];
                }
                continue;
            }

            // Display math $$...$$
            if (preg_match('/^\$\$/', $trimmed)) {
                $math = '';
                if (preg_match('/^\$\$(.+)\$\$$/', $trimmed, $m)) {
                    $segments[] = ['type' => 'math', 'content' => trim($m[1])];
                    $i++;
                    continue;
                }
                $i++;
                while ($i < $total && !preg_match('/^\$\$/', rtrim($lines[$i]))) {
                    $math .= $lines[$i] . "\n";
                    $i++;
                }
                $i++;
                if (trim($math) !== '') {
                    $segments[] = ['type' => 'math', 'content' => rtrim($math)];
                }
                continue;
            }

            // Heading
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $m)) {
                $segments[] = ['type' => 'header', 'content' => trim($m[2])];
                $i++;
                continue;
            }

            // Horizontal rule
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', $trimmed)) {
                $segments[] = ['type' => 'separator', 'content' => json_encode(['type' => 'divider'])];
                $i++;
                continue;
            }

            // Blockquote → note
            if (preg_match('/^>\s?(.*)$/', $trimmed, $m)) {
                $noteLines = [trim($m[1])];
                $i++;
                while ($i < $total) {
                    $nextRaw = rtrim($lines[$i]);
                    if (preg_match('/^>\s?(.*)$/', $nextRaw, $m2)) {
                        $noteLines[] = trim($m2[1]);
                        $i++;
                    } else {
                        break;
                    }
                }
                $segments[] = ['type' => 'note', 'content' => implode("\n", $noteLines)];
                continue;
            }

            // Image → photo
            if (preg_match('/^!\[.*?\]\((.+?)\)$/', $trimmed, $m)) {
                $url = trim($m[1]);
                // Convert full URL back to relative storage path so the block renderer works
                $segments[] = ['type' => 'photo', 'content' => $this->urlToStoragePath($url)];
                $i++;
                continue;
            }

            // Table
            if (preg_match('/^\|/', $trimmed)) {
                $tableLines = [];
                while ($i < $total && preg_match('/^\|/', rtrim($lines[$i]))) {
                    $tableLines[] = rtrim($lines[$i]);
                    $i++;
                }
                $tableData = $this->parseMarkdownTable($tableLines);
                if (!empty($tableData)) {
                    $segments[] = ['type' => 'table', 'content' => json_encode($tableData)];
                }
                continue;
            }

            // List
            if (preg_match('/^(\s*[-*+]|\s*\d+\.)\s+(.+)$/', $trimmed, $m)) {
                $isNumbered = preg_match('/^\s*\d+\./', $trimmed);
                $items      = [trim($m[2])];
                $i++;
                while ($i < $total && preg_match('/^(\s*[-*+]|\s*\d+\.)\s+(.+)$/', rtrim($lines[$i]), $m2)) {
                    $items[] = trim($m2[2]);
                    $i++;
                }
                $segments[] = [
                    'type'    => 'list',
                    'content' => json_encode(['style' => $isNumbered ? 'numbered' : 'bullet', 'items' => $items]),
                ];
                continue;
            }

            // Empty line
            if (trim($trimmed) === '') {
                $i++;
                continue;
            }

            // Plain paragraph
            $paraLines = [$trimmed];
            $i++;
            while ($i < $total) {
                $next = rtrim($lines[$i]);
                if ($next === '') break;
                if (preg_match('/^(#{1,6}\s|```|\$\$|>|!\[|-{3,}|\*{3,}|\||\s*[-*+]\s|\s*\d+\.\s)/', $next)) break;
                $paraLines[] = $next;
                $i++;
            }
            $segments[] = ['type' => 'description', 'content' => implode("\n", $paraLines)];
        }

        return $segments;
    }

    /**
     * Convert a full public URL to a relative storage path.
     * e.g. "http://localhost/storage/ai_images/5/1/img.png"
     *      → "ai_images/5/1/img.png"
     * If it's already a relative path, return as-is.
     */
    private function urlToStoragePath(string $url): string
    {
        // Already relative (no scheme)
        if (!preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        // Strip domain + /storage/ prefix
        $storagePubUrl = Storage::disk('public')->url('');
        if (str_starts_with($url, $storagePubUrl)) {
            return ltrim(substr($url, strlen($storagePubUrl)), '/');
        }

        // Fallback: strip anything up to /storage/
        if (preg_match('#/storage/(.+)$#', $url, $m)) {
            return $m[1];
        }

        // Can't resolve — return full URL, blade will handle gracefully
        return $url;
    }

    private function parseMarkdownTable(array $lines): array
    {
        $rows = [];
        foreach ($lines as $line) {
            if (preg_match('/^\|[\s\-|:]+\|$/', $line)) continue;
            $cells = array_map('trim', explode('|', trim($line, '|')));
            if (!empty(array_filter($cells, fn($c) => $c !== ''))) {
                $rows[] = $cells;
            }
        }
        return $rows;
    }

    // ── MinerU extraction (unchanged) ─────────────────────────────────────────
    private function extractWithMinerU(AiJob $aiJob, string $imagesAbsPath): array
    {
        $pdfAbsPath = Storage::disk('local')->path($aiJob->pdf_path);
        $scriptPath = base_path('scripts/mineru_extract.py');

        $aiJob->log("PDF: {$pdfAbsPath}", 'info');
        $aiJob->log("Script: {$scriptPath}", 'info');
        $aiJob->log("Images dir: {$imagesAbsPath}", 'info');

        if (!file_exists($scriptPath)) {
            throw new \RuntimeException("mineru_extract.py not found at {$scriptPath}.");
        }

        $python = $this->resolvePython();
        $aiJob->log("Python: {$python}", 'info');
        $aiJob->log('Launching MinerU subprocess…', 'info');

        $process = new Process(
            [$python, $scriptPath, $pdfAbsPath, $imagesAbsPath],
            null, self::pythonEnv(), null, 0
        );
        $process->run();

        $exitCode = $process->getExitCode();
        $stderr   = trim($process->getErrorOutput());
        $aiJob->log("MinerU exited with code {$exitCode}.", $exitCode === 0 ? 'info' : 'error');

        if ($stderr) {
            foreach (array_slice(explode("\n", $stderr), 0, 20) as $line) {
                if (trim($line)) $aiJob->log("  stderr: " . trim($line), 'warn');
            }
        }

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('MinerU process failed: ' . ($stderr ?: 'no stderr'));
        }

        $output = json_decode($process->getOutput(), true);

        if (!($output['success'] ?? false)) {
            $err    = $output['error']  ?? 'unknown';
            $detail = $output['detail'] ?? '';
            $aiJob->log("MinerU error: {$err}", 'error');
            if ($detail) $aiJob->log("Detail: {$detail}", 'error');
            throw new \RuntimeException('MinerU error: ' . $err);
        }

        $markdown = trim($output['markdown'] ?? '');
        if (empty($markdown)) {
            throw new \RuntimeException('MinerU returned empty Markdown. PDF may be image-only.');
        }

        $imagePaths = $output['images'] ?? [];
        $imageUrls  = [];
        foreach ($imagePaths as $absPath) {
            $relToPublic = ltrim(str_replace(
                Storage::disk('public')->path(''),
                '',
                $absPath
            ), DIRECTORY_SEPARATOR . '/');
            $imageUrls[] = Storage::disk('public')->url($relToPublic);
        }

        $aiJob->log("MinerU found " . count($imageUrls) . " images.", 'info');

        return ['markdown' => $markdown, 'imageUrls' => $imageUrls];
    }

    private function resolvePython(): string
    {
        $win  = base_path('scripts/.venv/Scripts/python.exe');
        $unix = base_path('scripts/.venv/bin/python3');
        if (file_exists($win))  return $win;
        if (file_exists($unix)) return $unix;
        return PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
    }

    private static function pythonEnv(): array
    {
        $env = array_merge($_SERVER, $_ENV);
        foreach (array_keys($env) as $key) {
            if (str_starts_with($key, 'HTTP_') || in_array($key, ['argc', 'argv', 'REQUEST_URI', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'QUERY_STRING'])) {
                unset($env[$key]);
            }
        }
        $env['PYTHONHASHSEED']   = '0';
        $env['PYTHONUNBUFFERED'] = '1';
        if (PHP_OS_FAMILY === 'Windows') {
            $env['SystemRoot'] ??= 'C:\\Windows';
            $env['SYSTEMROOT'] ??= 'C:\\Windows';
        }
        return $env;
    }
}
