<?php

namespace App\Jobs;

use App\Models\AiJob;
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

    /** No HTTP/worker timeout — MinerU + phi4 can take several minutes. */
    public int $timeout = 0;

    /** Surface failures immediately — no silent retries. */
    public int $tries = 1;

    public function __construct(public int $aiJobId) {}

    public function handle(): void
    {
        $aiJob = AiJob::findOrFail($this->aiJobId);
        $aiJob->update(['status' => 'processing', 'logs' => []]);
        $aiJob->log('Job picked up by worker.', 'info');
        $aiJob->log('Status → processing.', 'info');

        try {
            // ── STEP 1: MinerU extraction ──────────────────────────────
            $aiJob->log('STEP 1 — Starting MinerU PDF extraction.', 'info');
            $markdown = $this->extractWithMinerU($aiJob);
            $chars = mb_strlen($markdown);
            $aiJob->log("STEP 1 — Extraction complete. Got {$chars} characters of Markdown.", 'ok');

            // ── STEP 2: Ollama structuring ─────────────────────────────
            $aiJob->log('STEP 2 — Sending Markdown to Ollama (phi4) for structuring.', 'info');
            $aiJob->log('STEP 2 — This can take several minutes. No timeout set.', 'warn');
            $structured = $this->structureWithOllama($aiJob, $markdown);

            $chapterCount = count($structured['chapters'] ?? []);
            $lessonCount  = array_sum(array_map(
                fn($ch) => count($ch['lessons'] ?? []),
                $structured['chapters'] ?? []
            ));
            $blockCount = array_sum(array_map(
                fn($ch) => array_sum(array_map(
                    fn($l) => count($l['blocks'] ?? []),
                    $ch['lessons'] ?? []
                )),
                $structured['chapters'] ?? []
            ));

            $aiJob->log("STEP 2 — Ollama done. Chapters: {$chapterCount} | Lessons: {$lessonCount} | Blocks: {$blockCount}.", 'ok');

            // ── STEP 3: Persist ────────────────────────────────────────
            $aiJob->log('STEP 3 — Saving result JSON to database.', 'info');
            $aiJob->update([
                'status'      => 'done',
                'result_json' => json_encode(
                    $structured,
                    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                ),
            ]);
            $aiJob->log('STEP 3 — Done. Job complete. Ready to save as course.', 'ok');

        } catch (\Throwable $e) {
            $aiJob->log('FAILED — ' . $e->getMessage(), 'error');
            $aiJob->log('File: ' . $e->getFile() . ':' . $e->getLine(), 'error');
            $aiJob->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    // ── Step 1 ────────────────────────────────────────────────────────────────
    private function extractWithMinerU(AiJob $aiJob): string
    {
        $pdfAbsPath = Storage::disk('local')->path($aiJob->pdf_path);
        $scriptPath = base_path('scripts/mineru_extract.py');

        $aiJob->log("PDF path: {$pdfAbsPath}", 'info');
        $aiJob->log("MinerU script: {$scriptPath}", 'info');

        if (! file_exists($scriptPath)) {
            throw new \RuntimeException(
                "MinerU bridge not found at {$scriptPath}. " .
                "Copy mineru_extract.py to <laravel_root>/scripts/ and " .
                "run: pip install magic-pdf[full] --extra-index-url https://wheels.myhloli.com"
            );
        }

        $pythonWin   = base_path('scripts/.venv/Scripts/python.exe');
        $pythonUnix  = base_path('scripts/.venv/bin/python3');

        if (file_exists($pythonWin)) {
            $python = $pythonWin;
        } elseif (file_exists($pythonUnix)) {
            $python = $pythonUnix;
        } else {
            $python = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
        }

        $aiJob->log("Python executable: {$python}", 'info');
        $aiJob->log("Launching MinerU subprocess…", 'info');

        $process = new Process(
            [$python, $scriptPath, $pdfAbsPath],
            null,
            self::pythonEnv(),
            null,
            0
        );
        $process->run();

        $exitCode = $process->getExitCode();
        $stderr   = trim($process->getErrorOutput());
        $stdout   = trim($process->getOutput());

        $aiJob->log("MinerU process exited with code: {$exitCode}.", $exitCode === 0 ? 'info' : 'error');

        if ($stderr) {
            // Log stderr line by line so it's readable
            foreach (explode("\n", mb_substr($stderr, 0, 2000)) as $line) {
                if (trim($line)) $aiJob->log("  stderr: {$line}", 'warn');
            }
        }

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('MinerU process failed: ' . ($stderr ?: 'no stderr output'));
        }

        $output = json_decode($stdout, true);

        if (! ($output['success'] ?? false)) {
            $err = $output['error'] ?? 'unknown';
            $detail = $output['detail'] ?? '';
            $aiJob->log("MinerU returned failure: {$err}", 'error');
            if ($detail) $aiJob->log("Detail: {$detail}", 'error');
            throw new \RuntimeException('MinerU error: ' . $err);
        }

        $markdown = trim($output['markdown'] ?? '');

        if (empty($markdown)) {
            throw new \RuntimeException(
                'MinerU returned empty Markdown. PDF may be image-only.'
            );
        }

        return $markdown;
    }

    // ── Step 2 ────────────────────────────────────────────────────────────────
    /**
     * The model's ONLY task: split the document into chapters/lessons
     * and wrap each piece verbatim in a `markdown` block.
     * No content transformation, no categorization.
     */
    private function structureWithOllama(AiJob $aiJob, string $markdown): array
    {
        $maxChars  = 40000;
        $truncated = mb_strlen($markdown) > $maxChars
            ? mb_substr($markdown, 0, $maxChars) . "\n\n[...truncated...]"
            : $markdown;

        $originalLen = mb_strlen($markdown);
        $sentLen     = mb_strlen($truncated);
        $aiJob->log("Markdown length: {$originalLen} chars. Sending {$sentLen} chars to Ollama.", 'info');

        $year   = $aiJob->year;
        $branch = $aiJob->branch;

        $prompt = <<<PROMPT
You are a course structure builder. Your ONLY job: split a Markdown document into chapters and lessons. Copy content VERBATIM — never rewrite, summarise, or add text.

SPLITTING RULES:
- # headings → chapter boundaries
- ## headings → lesson boundaries
- If no # exists: one chapter for whole document
- If no ## exists: one lesson for the whole chapter
- Each lesson = exactly ONE block (type "markdown") containing ALL content of that lesson, verbatim

CONTENT RULES:
- Copy markdown exactly — keep LaTeX ($...$, $$...$$), tables, code fences, bullet lists
- Do NOT summarise, paraphrase, or add anything
- chapter_number and lesson_number start at 1 and count up

Return ONLY valid JSON — no fences, no explanation.

Required shape:
{
  "title": "<first # heading or first sentence>",
  "year": $year,
  "branch": "$branch",
  "description": "<one sentence>",
  "status": "draft",
  "chapters": [
    {
      "title": "<chapter # heading>",
      "description": "<first line of chapter>",
      "chapter_number": 1,
      "status": "draft",
      "lessons": [
        {
          "title": "<lesson ## heading>",
          "description": "<first line of lesson>",
          "lesson_number": 1,
          "status": "draft",
          "blocks": [
            {
              "type": "markdown",
              "content": "<ALL raw markdown of this lesson, verbatim>",
              "block_number": 1
            }
          ]
        }
      ]
    }
  ]
}

--- MARKDOWN DOCUMENT ---
$truncated
--- END ---
PROMPT;

        $aiJob->log("Posting to Ollama API (phi4, temperature=0, stream=false)…", 'info');

        $response = Http::timeout(0)
            ->withOptions(['connect_timeout' => 10])
            ->post('http://localhost:11434/api/generate', [
                'model'   => 'phi4',
                'prompt'  => $prompt,
                'stream'  => false,
                'format'  => 'json',
                'options' => ['temperature' => 0, 'num_predict' => -1],
            ]);

        if ($response->failed()) {
            $aiJob->log("Ollama HTTP error: status " . $response->status(), 'error');
            throw new \RuntimeException('Ollama HTTP error: ' . $response->status());
        }

        $aiJob->log("Ollama responded (HTTP 200). Parsing JSON…", 'info');

        $jsonString = $response->json('response') ?? '';

        if (empty($jsonString)) {
            throw new \RuntimeException('Ollama returned an empty response.');
        }

        $aiJob->log("Raw response length: " . mb_strlen($jsonString) . " chars.", 'info');

        // Strip accidental markdown fences
        $jsonString = preg_replace('/^```json\s*/i', '', trim($jsonString));
        $jsonString = preg_replace('/```\s*$/',        '', $jsonString);

        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $snippet = mb_substr($jsonString, 0, 300);
            $aiJob->log("JSON parse failed: " . json_last_error_msg(), 'error');
            $aiJob->log("Snippet: {$snippet}", 'error');
            throw new \RuntimeException(
                'Ollama output is not valid JSON: ' . json_last_error_msg()
            );
        }

        if (empty($decoded['chapters'])) {
            $aiJob->log("Ollama returned no chapters. Falling back to single-block structure.", 'warn');
            return $this->fallbackStructure($aiJob, $markdown);
        }

        $aiJob->log("JSON parsed successfully.", 'ok');
        return $decoded;
    }

    // ── Fallback ──────────────────────────────────────────────────────────────
    /** Ollama gave us nothing useful — wrap the whole Markdown in one block. */
    private function fallbackStructure(AiJob $aiJob, string $markdown): array
    {
        $title = 'Untitled Course';
        if (preg_match('/^#\s+(.+)$/m', $markdown, $m)) {
            $title = trim($m[1]);
        }
        $aiJob->log("Fallback: using title \"{$title}\" and wrapping all content in one block.", 'warn');

        return [
            'title'       => $title,
            'year'        => $aiJob->year,
            'branch'      => $aiJob->branch,
            'description' => 'Auto-extracted from PDF.',
            'status'      => 'draft',
            'chapters'    => [[
                'title'          => $title,
                'description'    => '',
                'chapter_number' => 1,
                'status'         => 'draft',
                'lessons'        => [[
                    'title'         => $title,
                    'description'   => '',
                    'lesson_number' => 1,
                    'status'        => 'draft',
                    'blocks'        => [[
                        'type'         => 'markdown',
                        'content'      => $markdown,
                        'block_number' => 1,
                    ]],
                ]],
            ]],
        ];
    }

    /**
     * Build a safe environment array for Python subprocesses on Windows.
     *
     * When PHP (running as a web server process) spawns a child process,
     * the inherited environment is often stripped down and missing the
     * Windows security APIs that Python needs to seed its hash randomizer.
     * Passing PYTHONHASHSEED explicitly bypasses the OS random-number call
     * that causes: "Fatal Python error: _Py_HashRandomization_Init"
     *
     * We merge the current PHP environment so PATH, SystemRoot, etc. are
     * inherited, then override/add the Python-specific keys we need.
     */
    private static function pythonEnv(): array
    {
        // Start with everything PHP can see
        $env = array_merge($_SERVER, $_ENV);

        // Remove keys that aren't real environment variables
        foreach (array_keys($env) as $key) {
            if (str_starts_with($key, 'HTTP_') || str_starts_with($key, 'argc') || $key === 'argv') {
                unset($env[$key]);
            }
        }

        // Required on Windows: give Python a fixed hash seed so it never
        // has to call the OS random-number API during startup.
        $env['PYTHONHASHSEED']    = '0';

        // Unbuffered output so we can read stdout line-by-line reliably.
        $env['PYTHONUNBUFFERED']  = '1';

        // Make sure SystemRoot is set — Python on Windows needs it for DLL loading.
        if (PHP_OS_FAMILY === 'Windows' && empty($env['SystemRoot'])) {
            $env['SystemRoot'] = 'C:\\Windows';
        }

        return $env;
    }
}
