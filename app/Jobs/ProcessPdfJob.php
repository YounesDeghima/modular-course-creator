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
    public int $tries   = 1;

    // When set, skip MinerU and use this markdown directly (for recut)
    public ?string $forcedMarkdown = null;

    public function __construct(public int $aiJobId, ?string $forcedMarkdown = null)
    {
        $this->forcedMarkdown = $forcedMarkdown;
    }

    public function handle(): void
    {
        $aiJob = AiJob::findOrFail($this->aiJobId);

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
            // ── STEP 1: MinerU (skip if recut with existing markdown) ─────────
            if ($this->forcedMarkdown !== null) {
                $markdown = $this->forcedMarkdown;
                $chars    = mb_strlen($markdown);
                $aiJob->log("STEP 1 — SKIPPED (recut mode). Using provided markdown ({$chars} chars).", 'info');
            } else {
                $aiJob->log('STEP 1 — Starting MinerU PDF extraction.', 'info');
                $markdown = $this->extractWithMinerU($aiJob);
                $chars    = mb_strlen($markdown);
                $aiJob->log("STEP 1 — Done. Extracted {$chars} chars of Markdown.", 'ok');
            }

            // ── Always store raw markdown in logs for recut ───────────────────
            $aiJob->log('__RAW_MARKDOWN_START__', 'info');
            $aiJob->log($markdown, 'raw');
            $aiJob->log('__RAW_MARKDOWN_END__', 'info');

            // ── STEP 2: Ollama with retry loop ────────────────────────────────
            $aiJob->log('STEP 2 — Sending to Ollama phi4 for structuring…', 'info');
            $structured = $this->structureWithOllamaWithRetries($aiJob, $markdown);

            $chCount = count($structured['chapters'] ?? []);
            $lCount  = array_sum(array_map(fn($c) => count($c['lessons'] ?? []), $structured['chapters'] ?? []));
            $bCount  = array_sum(array_map(fn($c) => array_sum(array_map(fn($l) => count($l['blocks'] ?? []), $c['lessons'] ?? [])), $structured['chapters'] ?? []));
            $aiJob->log("STEP 2 — Done. Chapters:{$chCount} Lessons:{$lCount} Blocks:{$bCount}.", 'ok');

            // ── STEP 3: Save ──────────────────────────────────────────────────
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
                $delay = min(30 * $attempt, 120);
                $aiJob->log("Will auto-retry in {$delay}s (attempt {$attempt}/{$aiJob->max_attempts}).", 'warn');
                self::dispatch($aiJob->id)->delay(now()->addSeconds($delay));
            } else {
                $aiJob->log("Max attempts ({$aiJob->max_attempts}) reached. No more retries.", 'error');
                $aiJob->log("TIP: Use the 'Recut' button to retry only the Ollama step using the saved markdown.", 'warn');
            }
        }
    }

    // ── Ollama with inner retry loop (3 tries before bubbling up) ─────────────
    private function structureWithOllamaWithRetries(AiJob $aiJob, string $markdown): array
    {
        $maxOllamaRetries = 3;
        $lastError        = '';

        for ($try = 1; $try <= $maxOllamaRetries; $try++) {
            try {
                $aiJob->log("STEP 2 — Ollama attempt {$try}/{$maxOllamaRetries}…", 'info');
                $result = $this->structureWithOllama($aiJob, $markdown);

                // Validate non-empty chapters
                if (empty($result['chapters'])) {
                    throw new \RuntimeException('Ollama returned empty chapters array.');
                }

                // Validate at least one lesson with at least one block
                $totalBlocks = array_sum(array_map(
                    fn($c) => array_sum(array_map(fn($l) => count($l['blocks'] ?? []), $c['lessons'] ?? [])),
                    $result['chapters']
                ));
                if ($totalBlocks === 0) {
                    throw new \RuntimeException('Ollama produced chapters/lessons but zero blocks.');
                }

                return $result;

            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $aiJob->log("STEP 2 — Ollama attempt {$try} failed: {$lastError}", 'warn');
                if ($try < $maxOllamaRetries) {
                    $aiJob->log("STEP 2 — Retrying in 5s…", 'warn');
                    sleep(5);
                }
            }
        }

        // All Ollama retries exhausted → use fallback
        $aiJob->log("STEP 2 — All Ollama retries exhausted. Using fallback structure.", 'error');
        $aiJob->log("STEP 2 — Last error: {$lastError}", 'error');
        return $this->fallbackStructure($aiJob, $markdown);
    }

    // ── Ollama structuring ────────────────────────────────────────────────────
    private function structureWithOllama(AiJob $aiJob, string $markdown): array
    {
        $maxChars  = 60000;
        $truncated = mb_strlen($markdown) > $maxChars
            ? mb_substr($markdown, 0, $maxChars) . "\n\n[...truncated...]"
            : $markdown;

        $origLen = mb_strlen($markdown);
        $sentLen = mb_strlen($truncated);
        $aiJob->log("Markdown {$origLen} chars → sending {$sentLen} chars to Ollama.", 'info');

        $year   = $aiJob->year;
        $branch = $aiJob->branch;

        // ── ENHANCED PROMPT ────────────────────────────────────────────────────
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
| Inline `$...$` heavy math     | "math"       | LaTeX expression verbatim                         |
| ` ```...``` ` fenced code     | "code"       | code verbatim (keep language tag)                 |
| `- ` / `* ` / `1.` list       | "list"       | JSON: {"style":"bullet","items":["a","b",...]}     |
| Markdown table `|---|`        | "table"      | JSON: [["col1","col2"],["val1","val2"],...]         |
| `![](images/...)` image tag   | "photo"      | the image path string only, e.g. "images/abc.jpg" |
| Horizontal rule `---` / `***` | "separator"  | JSON: {"type":"divider"}                           |
| Theorem / definition box      | "note"       | full text verbatim                                 |
| Algorithm / pseudocode block  | "code"       | full block verbatim                                |
| Any remaining prose           | "description"| text verbatim                                      |

IMPORTANT RULES:
1. Every `![](path)` line MUST become a {"type":"photo","content":"path"} block. Never skip images.
2. Every LaTeX block (`$$...$$`) MUST become a {"type":"math","content":"..."} block.
3. Never merge multiple different types into one block.
4. Never skip or summarise any content. Copy verbatim.
5. block_number starts at 1 per lesson and increments by 1.
6. "header" blocks must NOT contain the # characters — strip them.

═══════════════════════════════════════════════════════════════
OUTPUT FORMAT — return ONLY valid JSON, no markdown fences, no explanation
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
            {"type": "header",      "content": "Introduction",          "block_number": 1},
            {"type": "description", "content": "Some paragraph...",     "block_number": 2},
            {"type": "math",        "content": "$$f(x) = 0$$",          "block_number": 3},
            {"type": "photo",       "content": "images/fig1.jpg",        "block_number": 4},
            {"type": "list",        "content": "{\"style\":\"bullet\",\"items\":[\"item1\",\"item2\"]}", "block_number": 5}
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

        $aiJob->log('POST → http://localhost:11434/api/generate (phi4, stream=false)…', 'info');

        $response = Http::timeout(0)
            ->withOptions(['connect_timeout' => 10])
            ->post('http://localhost:11434/api/generate', [
                'model'   => 'phi4',
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
            throw new \RuntimeException('Ollama HTTP error: ' . $response->status());
        }

        $aiJob->log('Ollama responded HTTP 200. Parsing JSON…', 'info');
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

    // ── Fallback: one markdown block per lesson ───────────────────────────────
    private function fallbackStructure(AiJob $aiJob, string $markdown): array
    {
        $title = 'Untitled Course';
        if (preg_match('/^#\s+(.+)$/m', $markdown, $m)) {
            $title = trim($m[1]);
        }
        $aiJob->log("Fallback: title=\"{$title}\"", 'warn');

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
                    'blocks'        => [['type' => 'markdown', 'content' => $markdown, 'block_number' => 1]],
                ]],
            ]],
        ];
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
