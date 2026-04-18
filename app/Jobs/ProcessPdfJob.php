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

    public int $timeout = 0;
    public int $tries   = 1; // Laravel-level retries off; we handle retries ourselves

    public function __construct(public int $aiJobId) {}

    public function handle(): void
    {
        $aiJob = AiJob::findOrFail($this->aiJobId);

        // Skip cancelled jobs
        if ($aiJob->status === 'cancelled') {
            return;
        }

        $attempt = ($aiJob->attempt ?? 0) + 1;
        $aiJob->update([
            'status'     => 'processing',
            'attempt'    => $attempt,
            'started_at' => now(),
            'logs'       => [],
        ]);

        $aiJob->log("═══ Attempt {$attempt}/{$aiJob->max_attempts} ═══", 'info');
        $aiJob->log('Job picked up by worker.', 'info');

        $startTime = microtime(true);

        try {
            // ── STEP 1: MinerU ────────────────────────────────────────
            $aiJob->log('STEP 1 — Starting MinerU PDF extraction.', 'info');
            $markdown = $this->extractWithMinerU($aiJob);
            $chars    = mb_strlen($markdown);
            $aiJob->log("STEP 1 — Done. Extracted {$chars} chars of Markdown.", 'ok');

            // ── STEP 2: Ollama ────────────────────────────────────────
            $aiJob->log('STEP 2 — Sending to Ollama phi4 for structuring…', 'info');
            $aiJob->log('STEP 2 — No timeout. Large PDFs can take minutes.', 'warn');
            $structured = $this->structureWithOllama($aiJob, $markdown);

            $chCount = count($structured['chapters'] ?? []);
            $lCount  = array_sum(array_map(fn($c) => count($c['lessons'] ?? []), $structured['chapters'] ?? []));
            $bCount  = array_sum(array_map(fn($c) => array_sum(array_map(fn($l) => count($l['blocks'] ?? []), $c['lessons'] ?? [])), $structured['chapters'] ?? []));
            $aiJob->log("STEP 2 — Done. Chapters:{$chCount} Lessons:{$lCount} Blocks:{$bCount}.", 'ok');

            // ── STEP 3: Save ──────────────────────────────────────────
            $aiJob->log('STEP 3 — Persisting result JSON…', 'info');
            $duration = (int)(microtime(true) - $startTime);
            $aiJob->update([
                'status'           => 'done',
                'result_json'      => json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'finished_at'      => now(),
                'duration_seconds' => $duration,
            ]);
            $aiJob->log("STEP 3 — Complete in {$duration}s. Ready to save as course.", 'ok');

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
                $delay = min(30 * $attempt, 120); // 30s, 60s, 120s
                $aiJob->log("Will auto-retry in {$delay}s (attempt {$attempt}/{$aiJob->max_attempts}).", 'warn');
                // Re-dispatch with delay — creates a fresh job in the queue
                self::dispatch($aiJob->id)->delay(now()->addSeconds($delay));
            } else {
                $aiJob->log("Max attempts ({$aiJob->max_attempts}) reached. No more retries.", 'error');
            }
        }
    }

    // ── MinerU extraction ─────────────────────────────────────────────────────
    private function extractWithMinerU(AiJob $aiJob): string
    {
        $pdfAbsPath = Storage::disk('local')->path($aiJob->pdf_path);
        $scriptPath = base_path('scripts/mineru_extract.py');

        $aiJob->log("PDF: {$pdfAbsPath}", 'info');
        $aiJob->log("Script: {$scriptPath}", 'info');

        if (!file_exists($scriptPath)) {
            throw new \RuntimeException("mineru_extract.py not found at {$scriptPath}.");
        }

        $python = $this->resolvePython();
        $aiJob->log("Python: {$python}", 'info');
        $aiJob->log('Launching MinerU subprocess…', 'info');

        $process = new Process([$python, $scriptPath, $pdfAbsPath], null, self::pythonEnv(), null, 0);
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

        return $markdown;
    }

    // ── Ollama structuring ────────────────────────────────────────────────────
    private function structureWithOllama(AiJob $aiJob, string $markdown): array
    {
        $maxChars  = 40000;
        $truncated = mb_strlen($markdown) > $maxChars
            ? mb_substr($markdown, 0, $maxChars) . "\n\n[...truncated...]"
            : $markdown;

        $origLen = mb_strlen($markdown);
        $sentLen = mb_strlen($truncated);
        $aiJob->log("Markdown {$origLen} chars → sending {$sentLen} chars to Ollama.", 'info');

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
- Copy markdown exactly — keep LaTeX, tables, code fences, bullet lists
- Do NOT summarise, paraphrase, or add anything
- chapter_number and lesson_number start at 1

Return ONLY valid JSON — no fences, no explanation.

{
  "title": "<first # heading>",
  "year": $year,
  "branch": "$branch",
  "description": "<one sentence>",
  "status": "draft",
  "chapters": [
    {
      "title": "...", "description": "...", "chapter_number": 1, "status": "draft",
      "lessons": [
        {
          "title": "...", "description": "...", "lesson_number": 1, "status": "draft",
          "blocks": [{ "type": "markdown", "content": "<verbatim markdown>", "block_number": 1 }]
        }
      ]
    }
  ]
}

--- MARKDOWN DOCUMENT ---
$truncated
--- END ---
PROMPT;

        $aiJob->log('POST → http://localhost:11434/api/generate (phi4, stream=false)…', 'info');

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
            $aiJob->log('Ollama HTTP error: ' . $response->status(), 'error');
            throw new \RuntimeException('Ollama HTTP error: ' . $response->status());
        }

        $aiJob->log('Ollama responded HTTP 200. Parsing JSON…', 'info');
        $jsonString = $response->json('response') ?? '';

        if (empty($jsonString)) {
            throw new \RuntimeException('Ollama returned an empty response.');
        }

        $aiJob->log('Response length: ' . mb_strlen($jsonString) . ' chars.', 'info');

        $jsonString = preg_replace('/^```json\s*/i', '', trim($jsonString));
        $jsonString = preg_replace('/```\s*$/', '', $jsonString);
        $decoded    = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $snippet = mb_substr($jsonString, 0, 300);
            $aiJob->log('JSON parse error: ' . json_last_error_msg(), 'error');
            $aiJob->log('Snippet: ' . $snippet, 'error');
            throw new \RuntimeException('Ollama output is not valid JSON: ' . json_last_error_msg());
        }

        if (empty($decoded['chapters'])) {
            $aiJob->log('No chapters found — using fallback single-block structure.', 'warn');
            return $this->fallbackStructure($aiJob, $markdown);
        }

        $aiJob->log('JSON parsed OK.', 'ok');
        return $decoded;
    }

    private function fallbackStructure(AiJob $aiJob, string $markdown): array
    {
        $title = 'Untitled Course';
        if (preg_match('/^#\s+(.+)$/m', $markdown, $m)) $title = trim($m[1]);
        $aiJob->log("Fallback: title=\"{$title}\"", 'warn');

        return [
            'title' => $title, 'year' => $aiJob->year, 'branch' => $aiJob->branch,
            'description' => 'Auto-extracted from PDF.', 'status' => 'draft',
            'chapters' => [[
                'title' => $title, 'description' => '', 'chapter_number' => 1, 'status' => 'draft',
                'lessons' => [[
                    'title' => $title, 'description' => '', 'lesson_number' => 1, 'status' => 'draft',
                    'blocks' => [['type' => 'markdown', 'content' => $markdown, 'block_number' => 1]],
                ]],
            ]],
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
