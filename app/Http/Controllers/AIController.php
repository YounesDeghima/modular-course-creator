<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPdfJob;
use App\Models\AiJob;
use App\Models\block;
use App\Models\chapter;
use App\Models\course;
use App\Models\lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AIController extends Controller
{
    // ── Test Ollama ───────────────────────────────────────────────────────────
    public function test(Request $request)
    {
        try {
            $response = Http::timeout(30)->post('http://localhost:11434/api/generate', [
                'model'  => 'phi4',
                'prompt' => 'Reply with exactly: {"status":"ok","message":"Ollama phi4 is running"}',
                'stream' => false,
                'format' => 'json',
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Ollama did not respond. Is it running on port 11434?',
                ], 502);
            }

            return response()->json([
                'ok'      => true,
                'message' => 'Ollama (phi4) is reachable!',
                'raw'     => $response->json(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ── Upload PDF → queue ────────────────────────────────────────────────────
    public function jsonify(Request $request)
    {
        $request->validate([
            'pdf_file'      => 'required|file|mimes:pdf|max:102400',
            'course_year'   => 'required|in:1,2,3',
            'course_branch' => 'nullable|in:mi,st,none',
        ]);

        $path = $request->file('pdf_file')->store('ai_uploads', 'local');

        $aiJob = AiJob::create([
            'status'   => 'queued',
            'pdf_path' => $path,
            'year'     => $request->course_year,
            'branch'   => $request->course_branch ?? 'none',
        ]);

        ProcessPdfJob::dispatch($aiJob->id);

        return response()->json([
            'job_id'  => $aiJob->id,
            'status'  => 'queued',
            'message' => 'PDF queued for processing.',
        ]);
    }

    // ── Poll status ───────────────────────────────────────────────────────────
    public function status($id)
    {
        $aiJob = AiJob::findOrFail($id);

        $data = [
            'id'         => $aiJob->id,
            'status'     => $aiJob->status,
            'created_at' => $aiJob->created_at,
            'updated_at' => $aiJob->updated_at,
        ];

        if ($aiJob->status === 'done')   { $data['result'] = json_decode($aiJob->result_json, true); }
        if ($aiJob->status === 'failed') { $data['error']  = $aiJob->error_message; }

        return response()->json($data);
    }

    // ── Active jobs ───────────────────────────────────────────────────────────
    public function activeJobs()
    {
        return response()->json(
            AiJob::whereNotIn('status', ['saved'])
                ->orderBy('created_at', 'desc')
                ->get(['id', 'status', 'created_at', 'updated_at'])
        );
    }

    // ── Save result to DB ─────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate(['job_id' => 'required|exists:ai_jobs,id']);

        $aiJob = AiJob::findOrFail($request->job_id);

        if ($aiJob->status !== 'done') {
            return response()->json(['error' => 'Job is not finished yet.'], 422);
        }

        $data = json_decode($aiJob->result_json, true);
        if (! $data) {
            return response()->json(['error' => 'Result JSON is invalid.'], 422);
        }

        $courseRecord = course::create([
            'title'       => $data['title']      ?? 'Untitled Course',
            'year'        => $data['year']        ?? $aiJob->year,
            'branch'      => $data['branch']      ?? $aiJob->branch,
            'description' => $data['description'] ?? '',
            'status'      => 'draft',
        ]);

        foreach (($data['chapters'] ?? []) as $chIdx => $chData) {
            $chapterRecord = chapter::create([
                'course_id'      => $courseRecord->id,
                'title'          => $chData['title']          ?? 'Chapter ' . ($chIdx + 1),
                'description'    => $chData['description']    ?? '',
                'chapter_number' => $chData['chapter_number'] ?? ($chIdx + 1),
                'status'         => 'draft',
            ]);

            foreach (($chData['lessons'] ?? []) as $lIdx => $lData) {
                $lessonRecord = lesson::create([
                    'chapter_id'    => $chapterRecord->id,
                    'title'         => $lData['title']         ?? 'Lesson ' . ($lIdx + 1),
                    'description'   => $lData['description']   ?? '',
                    'lesson_number' => $lData['lesson_number'] ?? ($lIdx + 1),
                    'content'       => '',
                    'status'        => 'draft',
                ]);

                foreach (($lData['blocks'] ?? []) as $bIdx => $bData) {
                    $type    = $bData['type']    ?? 'markdown';
                    $content = is_string($bData['content']) ? $bData['content'] : '';

                    $blk = block::create([
                        'lesson_id'    => $lessonRecord->id,
                        'type'         => $type,
                        'content'      => $content,
                        'block_number' => $bData['block_number'] ?? ($bIdx + 1),
                    ]);

                    if ($type === 'exercise') {
                        $blk->solutions()->create([
                            'solution_number' => 1,
                            'content'         => 'nothing here yet',
                        ]);
                    }
                }
            }
        }

        $aiJob->update(['status' => 'saved']);

        return response()->json([
            'success'   => true,
            'course_id' => $courseRecord->id,
            'message'   => 'Course saved as draft. Open the editor to upgrade markdown blocks.',
        ]);
    }

    // ── Convert block ─────────────────────────────────────────────────────────
    /**
     * POST /admin/ai/convert-block
     * Teacher "upgrade" flow: turn a markdown block into an interactive type.
     * The original markdown text is pre-filled so the teacher only adjusts.
     */
    public function convertBlock(Request $request)
    {
        $request->validate([
            'block_id'    => 'required|exists:blocks,id',
            'target_type' => 'required|in:header,description,note,exercise,code,math,
                              graph,table,function,list,separator,ext,photo,video,markdown',
        ]);

        $blk        = block::findOrFail($request->block_id);
        $targetType = trim($request->target_type);
        $rawContent = $blk->content;

        $newContent = $this->convertContent($rawContent, $targetType);

        $blk->update(['type' => $targetType, 'content' => $newContent]);

        if ($targetType === 'exercise' && $blk->solutions()->count() === 0) {
            $blk->solutions()->create(['solution_number' => 1, 'content' => 'nothing here yet']);
        }

        return response()->json(['success' => true, 'block' => $blk->fresh()]);
    }

    // ── Content converter ─────────────────────────────────────────────────────
    private function convertContent(string $raw, string $targetType): string
    {
        $plain = trim(strip_tags(
            preg_replace(['/#{1,6}\s+/', '/\*\*(.+?)\*\*/', '/\*(.+?)\*/'], ['', '$1', '$1'], $raw)
        ));

        return match ($targetType) {
            'header', 'description', 'note', 'code', 'math', 'exercise', 'markdown'
            => $raw,   // keep full markdown — teacher edits in-place

            'table' => json_encode(
                [['Column 1', 'Column 2'], [$plain, '']],
                JSON_UNESCAPED_UNICODE
            ),

            'graph' => json_encode(
                ['type' => 'line', 'labels' => ['A', 'B', 'C'], 'data' => [0, 0, 0]],
                JSON_UNESCAPED_UNICODE
            ),

            'function' => json_encode([
                'function' => 'sin(x)',
                'x_min' => -10, 'x_max' => 10,
                'y_min' => -5,  'y_max' => 5,
                'color' => '#4f46e5', 'step' => 0.1,
            ], JSON_UNESCAPED_UNICODE),

            'list' => json_encode([
                'style' => 'bullet',
                'items' => array_values(array_filter(
                    array_map('trim', preg_split('/\n|,/', $plain))
                )),
            ], JSON_UNESCAPED_UNICODE),

            'separator' => json_encode(['type' => 'divider']),

            'photo', 'video' => '',
            'ext'            => $raw,

            default => $raw,
        };
    }

    // ── Job logs ─────────────────────────────────────────────────────────────
    /**
     * GET /admin/ai/logs/{id}
     * Returns the full log array + current status for a job.
     * Lightweight — only reads two columns.
     */
    public function logs($id)
    {
        $aiJob = AiJob::findOrFail($id);

        return response()->json([
            'id'     => $aiJob->id,
            'status' => $aiJob->status,
            'logs'   => $aiJob->logs ?? [],
            'error'  => $aiJob->error_message,
        ]);
    }

    // ── Test MinerU ───────────────────────────────────────────────────────────
    public function testMinerU(Request $request)
    {
        $scriptPath = base_path('scripts/mineru_extract.py');

        // 1. Script exists?
        if (! file_exists($scriptPath)) {
            return response()->json([
                'ok'    => false,
                'step'  => 'script_missing',
                'error' => 'mineru_extract.py not found at ' . $scriptPath,
            ], 422);
        }

        // 2. Resolve python
        $pythonWin  = base_path('scripts/.venv/Scripts/python.exe');
        $pythonUnix = base_path('scripts/.venv/bin/python3');

        if (file_exists($pythonWin))      $python = $pythonWin;
        elseif (file_exists($pythonUnix)) $python = $pythonUnix;
        else                              $python = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';

        // 3. Check mineru imports
        $importCheck = new \Symfony\Component\Process\Process(
            [$python, '-c', 'import mineru; print("mineru_ok")'],
            null, $this->pythonEnv(), null, 30
        );
        $importCheck->run();

        if (! str_contains($importCheck->getOutput(), 'mineru_ok')) {
            return response()->json([
                'ok'     => false,
                'step'   => 'import_failed',
                'error'  => 'Could not import mineru. Run: pip install mineru[pipeline] inside your venv.',
                'detail' => trim($importCheck->getErrorOutput()),
            ], 422);
        }

        // 4. Check mineru CLI executable exists (no fake PDF needed)
        $scriptsDir = dirname($python);
        $mineruExe  = $scriptsDir . DIRECTORY_SEPARATOR .
            (PHP_OS_FAMILY === 'Windows' ? 'mineru.exe' : 'mineru');

        if (! file_exists($mineruExe)) {
            return response()->json([
                'ok'    => false,
                'step'  => 'cli_missing',
                'error' => "mineru executable not found at {$mineruExe}. Run: pip install mineru[pipeline]",
            ], 422);
        }

        // 5. Run `mineru --version` — proves the CLI works without needing a real PDF
        $versionCheck = new \Symfony\Component\Process\Process(
            [$mineruExe, '--version'],
            null, $this->pythonEnv(), null, 30
        );
        $versionCheck->run();

        $versionOut = trim($versionCheck->getOutput() . $versionCheck->getErrorOutput());

        // --version may exit non-zero on some builds but still print the version
        if (empty($versionOut)) {
            return response()->json([
                'ok'    => false,
                'step'  => 'cli_error',
                'error' => 'mineru CLI did not respond.',
            ], 422);
        }

        return response()->json([
            'ok'      => true,
            'message' => 'MinerU is installed and ready.',
            'version' => $versionOut,
            'python'  => $python,
            'cli'     => $mineruExe,
        ]);
    }

    /**
     * Safe environment for Python subprocesses on Windows.
     * Fixes: "Fatal Python error: _Py_HashRandomization_Init:
     *         failed to get random numbers to initialize Python"
     *
     * Root cause: PHP's web-server process runs with a restricted environment.
     * When Symfony Process spawns Python, it inherits that restricted env and
     * Python can't call the Windows CryptGenRandom API to seed its hash.
     * Passing PYTHONHASHSEED=0 bypasses that OS call entirely.
     */
    private function pythonEnv(): array
    {
        // Merge everything PHP can see
        $env = array_merge($_SERVER, $_ENV);

        // Strip non-env keys injected by PHP/web server
        foreach (array_keys($env) as $key) {
            if (str_starts_with($key, 'HTTP_') ||
                str_starts_with($key, 'DOCUMENT_') ||
                in_array($key, ['argc', 'argv', 'REQUEST_URI', 'SCRIPT_NAME',
                    'SCRIPT_FILENAME', 'QUERY_STRING'])) {
                unset($env[$key]);
            }
        }

        // Core fix: bypass OS random-number call on Windows
        $env['PYTHONHASHSEED']   = '0';
        // Unbuffered so stdout is readable immediately
        $env['PYTHONUNBUFFERED'] = '1';
        // Python on Windows needs SystemRoot for DLL resolution
        if (PHP_OS_FAMILY === 'Windows') {
            $env['SystemRoot'] ??= 'C:\\Windows';
            $env['SYSTEMROOT'] ??= 'C:\\Windows';
        }

        return $env;
    }
}
