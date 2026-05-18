<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\AiJobSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

        $model   = $this->recutModel ?? $aiJob->model ?? 'phi4';
        $attempt = ($aiJob->attempt ?? 0) + 1;

        $aiJob->update([
            'status'     => 'processing',
            'attempt'    => $attempt,
            'started_at' => now(),
        ]);

        $aiJob->log("═══ Attempt {$attempt}/{$aiJob->max_attempts} | model={$model} ═══", 'info');

        $startTime = microtime(true);

        try {
            // ─────────────────────────────────────────────────────────────────
            // RECUT MODE: skip MinerU, use existing snapshot
            // ─────────────────────────────────────────────────────────────────
            if ($this->recutSnapshotId !== null) {
                $snapshot = AiJobSnapshot::findOrFail($this->recutSnapshotId);
                $aiJob->log("RECUT mode → snapshot #{$snapshot->md_index} (id={$snapshot->id})", 'info');
                $this->runOllamaOnSnapshot($aiJob, $snapshot, $model, $startTime);
                return;
            }

            // ─────────────────────────────────────────────────────────────────
            // FULL MODE: MinerU → Ollama
            // ─────────────────────────────────────────────────────────────────

            // Next md_index for this job
            $mdIndex = AiJobSnapshot::where('ai_job_id', $aiJob->id)->count() + 1;

            // Persistent output dir in public storage so images are servable
            $imagesRelPath = "ai_images/{$aiJob->id}/{$mdIndex}";
            $imagesAbsPath = Storage::disk('public')->path($imagesRelPath);

            $aiJob->log("STEP 1 — Starting MinerU PDF extraction (md #{$mdIndex}).", 'info');

            // Create snapshot record early (md_status=processing)
            $snapshot = AiJobSnapshot::create([
                'ai_job_id'    => $aiJob->id,
                'md_index'     => $mdIndex,
                'markdown'     => null,
                'images_path'  => $imagesRelPath,
                'image_urls'   => [],
                'md_status'    => 'processing',
                'md_error'     => null,
                'md_created_at'=> now(),
                'results'      => [],
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
                $snapshot->update([
                    'md_status' => 'failed',
                    'md_error'  => $e->getMessage(),
                ]);
                throw $e; // bubble up → job fails
            }

            // ── STEP 2: Ollama ────────────────────────────────────────────────
            $this->runOllamaOnSnapshot($aiJob, $snapshot, $model, $startTime);

        } catch (\Throwable $e) {
            $duration = (int)(microtime(true) - $startTime);
            $aiJob->log('FAILED — ' . $e->getMessage(), 'error');
            $aiJob->log('At: ' . basename($e->getFile()) . ':' . $e->getLine(), 'error');

            $willRetry = $attempt < ($aiJob->max_attempts ?? 3);

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
                $aiJob->log("Max attempts ({$aiJob->max_attempts}) reached.", 'error');
                $aiJob->log("Use 'Retry MD' or 'Retry Cut' buttons per snapshot.", 'warn');
            }
        }
    }

    // ── Run Ollama on a snapshot (with inner retry loop) ──────────────────────
    private function runOllamaOnSnapshot(
        AiJob           $aiJob,
        AiJobSnapshot   $snapshot,
        string          $model,
        float           $startTime
    ): void {
        $markdown = $snapshot->markdown ?? '';

        $aiJob->log("STEP 2 — Sending snapshot #{$snapshot->md_index} to Ollama [{$model}]…", 'info');

        $ollamaStart = microtime(true);
        $maxRetries  = 3;
        $lastError   = '';
        $resultJson  = null;

        for ($try = 1; $try <= $maxRetries; $try++) {
            try {
                $aiJob->log("STEP 2 — Ollama attempt {$try}/{$maxRetries}…", 'info');
                $structured = $this->structureWithOllama($aiJob, $snapshot, $model);

                if (empty($structured['chapters'])) {
                    throw new \RuntimeException('Ollama returned empty chapters.');
                }
                $totalBlocks = array_sum(array_map(
                    fn($c) => array_sum(array_map(fn($l) => count($l['blocks'] ?? []), $c['lessons'] ?? [])),
                    $structured['chapters']
                ));
                if ($totalBlocks === 0) {
                    throw new \RuntimeException('Ollama produced zero blocks.');
                }

                $resultJson = json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                $ch = count($structured['chapters'] ?? []);
                $ls = array_sum(array_map(fn($c) => count($c['lessons'] ?? []), $structured['chapters'] ?? []));
                $aiJob->log("STEP 2 — OK. Chapters:{$ch} Lessons:{$ls} Blocks:{$totalBlocks}.", 'ok');

                break; // success

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

        if ($resultJson === null) {
            // ── ALL RETRIES FAILED — record failure, do NOT fake success ──────
            $aiJob->log("STEP 2 — All Ollama retries exhausted. Last error: {$lastError}", 'error');
            $aiJob->log("Use 'Retry Cut' on snapshot #{$snapshot->md_index} to try again.", 'warn');

            $snapshot->addResult($model, 'failed', null, $lastError, $ollamaDuration);

            $aiJob->update([
                'status'           => 'failed',
                'error_message'    => "Ollama failed on snapshot #{$snapshot->md_index}: {$lastError}",
                'finished_at'      => now(),
                'duration_seconds' => (int)(microtime(true) - $startTime),
            ]);
            return;
        }

        // ── SUCCESS ───────────────────────────────────────────────────────────
        $snapshot->addResult($model, 'done', $resultJson, null, $ollamaDuration);

        $totalDuration = (int)(microtime(true) - $startTime);
        $aiJob->log("STEP 3 — Persisting result. Total time: {$totalDuration}s.", 'ok');

        $aiJob->update([
            'status'           => 'done',
            'result_json'      => $resultJson,   // latest successful result
            'finished_at'      => now(),
            'duration_seconds' => $totalDuration,
            'error_message'    => null,
        ]);
    }

    // ── Ollama structuring (single attempt) ───────────────────────────────────
    private function structureWithOllama(AiJob $aiJob, AiJobSnapshot $snapshot, string $model): array
    {
        $markdown = $snapshot->markdown ?? '';
        $maxChars = 60000;
        $truncated = mb_strlen($markdown) > $maxChars
            ? mb_substr($markdown, 0, $maxChars) . "\n\n[...truncated...]"
            : $markdown;

        $origLen = mb_strlen($markdown);
        $sentLen = mb_strlen($truncated);
        $aiJob->log("Markdown {$origLen} chars → sending {$sentLen} chars to [{$model}].", 'info');

        // Inject image references into the markdown context for Ollama
        $imageUrls = $snapshot->image_urls ?? [];
        $imageNote = '';
        if (!empty($imageUrls)) {
            $imageNote = "\n\nAVAILABLE IMAGES (use these URLs in photo blocks):\n";
            foreach ($imageUrls as $i => $url) {
                $imageNote .= "  Image " . ($i + 1) . ": {$url}\n";
            }
        }

        $year   = $aiJob->year;
        $branch = $aiJob->branch;

        $prompt = <<<PROMPT
You are a strict course content parser. Convert the Markdown document below into a JSON course structure.

═══════════════════════════════════════════════════════════════
CHAPTER / LESSON SPLITTING RULES
═══════════════════════════════════════════════════════════════
- Lines starting with `# ` → new CHAPTER
- Lines starting with `## ` → new LESSON inside the current chapter
- If no `# ` exists → one chapter for the whole document
- If no `## ` exists → one lesson for the whole chapter

═══════════════════════════════════════════════════════════════
BLOCK SPLITTING RULES  (most important part)
═══════════════════════════════════════════════════════════════
Each lesson must be split into MULTIPLE blocks. Do NOT dump everything into a single markdown block.
Scan the content sequentially and emit one block per detected segment:

| Detected content              | block type   | content field                                      |
|-------------------------------|--------------|----------------------------------------------------|
| `# ` or `## ` heading text    | "header"     | plain heading text (no # symbols)                 |
| `### ` or `#### ` sub-heading | "header"     | plain heading text                                 |
| Plain paragraph text          | "description"| paragraph text verbatim                           |
| `> ` blockquote / note        | "note"       | text inside the blockquote                        |
| `$$...$$` or `\[...\]` LaTeX  | "math"       | LaTeX expression verbatim (keep delimiters)       |
| Fenced code block             | "code"       | code verbatim (no fences)                         |
| Image reference ![...](...) or image URL from AVAILABLE IMAGES list | "photo" | the image URL |
| Bullet / numbered list        | "list"       | {"style":"bullet","items":["item1","item2"]}      |
| Table                         | "table"      | [["H1","H2"],["r1c1","r1c2"]]  (JSON array)       |
| `---` horizontal rule         | "separator"  | {"type":"divider"}                                |

CRITICAL: When you see an image reference in the markdown (e.g. ![fig](path)) OR an entry from
the AVAILABLE IMAGES list below, always emit a "photo" block with the full image URL as content.
Do NOT skip images.
{$imageNote}

═══════════════════════════════════════════════════════════════
OUTPUT FORMAT (pure JSON, no markdown fences)
═══════════════════════════════════════════════════════════════
{
  "title": "<first # heading or filename>",
  "year": $year,
  "branch": "$branch",
  "description": "<one sentence summary>",
  "status": "draft",
  "chapters": [
    {
      "title": "...",
      "description": "...",
      "chapter_number": 1,
      "status": "draft",
      "lessons": [
        {
          "title": "...",
          "description": "...",
          "lesson_number": 1,
          "status": "draft",
          "blocks": [
            {"type": "header",      "content": "Introduction",                                      "block_number": 1},
            {"type": "description", "content": "Some paragraph...",                                 "block_number": 2},
            {"type": "math",        "content": "$$ f(x) = 0$$",                                     "block_number": 3},
            {"type": "photo",       "content": "https://example.com/storage/ai_images/1/1/fig.png","block_number": 4},
            {"type": "list",        "content": "{\"style\":\"bullet\",\"items\":[\"item1\"]}",      "block_number": 5}
          ]
        }
      ]
    }
  ]
}

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
                    'num_predict' => -1,
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
        $jsonString = preg_replace('/^```\s*/i', '', $jsonString);
        $jsonString = preg_replace('/```\s*$/', '', $jsonString);

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

    // ── MinerU extraction ─────────────────────────────────────────────────────
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

        // Build public URLs for images
        $imagePaths = $output['images'] ?? [];
        $imageUrls  = [];
        foreach ($imagePaths as $absPath) {
            // Make path relative to storage/app/public for URL generation
            $relToPublic = ltrim(str_replace(
                Storage::disk('public')->path(''),
                '',
                $absPath
            ), DIRECTORY_SEPARATOR . '/');
            $imageUrls[] = Storage::disk('public')->url($relToPublic);
        }

        $aiJob->log("MinerU found " . count($imageUrls) . " images.", 'info');

        return [
            'markdown'  => $markdown,
            'imageUrls' => $imageUrls,
        ];
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
