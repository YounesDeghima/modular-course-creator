<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPdfJob;
use App\Models\AiJob;
use App\Models\block;
use App\Models\chapter;
use App\Models\course;
use App\Models\lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AIController extends Controller
{
    // ── Control panel view ────────────────────────────────────────────────────
    public function panel()
    {
        $user = Auth::user();
        return view('pages.admin.ai-panel', [
            'name'  => $user?->name ?? 'Guest',
            'email' => $user?->email ?? '',
            'id'    => $user?->id ?? null,
        ]);
    }

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
                return response()->json(['error' => 'Ollama did not respond'], 502);
            }
            return response()->json(['ok' => true, 'message' => 'Ollama (phi4) is reachable!', 'raw' => $response->json()]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ── Test MinerU ───────────────────────────────────────────────────────────
    public function testMinerU(Request $request)
    {
        $scriptPath = base_path('scripts/mineru_extract.py');
        if (!file_exists($scriptPath)) {
            return response()->json(['ok' => false, 'step' => 'script_missing', 'error' => 'mineru_extract.py not found at ' . $scriptPath], 422);
        }

        $python = $this->resolvePython();

        $importCheck = new \Symfony\Component\Process\Process(
            [$python, '-c', 'import mineru; print("mineru_ok")'],
            null, $this->pythonEnv(), null, 30
        );
        $importCheck->run();

        if (!str_contains($importCheck->getOutput(), 'mineru_ok')) {
            return response()->json(['ok' => false, 'step' => 'import_failed', 'error' => 'Could not import mineru.', 'detail' => trim($importCheck->getErrorOutput())], 422);
        }

        $scriptsDir = dirname($python);
        $mineruExe  = $scriptsDir . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'mineru.exe' : 'mineru');
        if (!file_exists($mineruExe)) {
            return response()->json(['ok' => false, 'step' => 'cli_missing', 'error' => "mineru executable not found at {$mineruExe}."], 422);
        }

        $versionCheck = new \Symfony\Component\Process\Process([$mineruExe, '--version'], null, $this->pythonEnv(), null, 30);
        $versionCheck->run();
        $versionOut = trim($versionCheck->getOutput() . $versionCheck->getErrorOutput());

        if (empty($versionOut)) {
            return response()->json(['ok' => false, 'step' => 'cli_error', 'error' => 'mineru CLI did not respond.'], 422);
        }

        return response()->json(['ok' => true, 'message' => 'MinerU is installed and ready.', 'version' => $versionOut, 'python' => $python, 'cli' => $mineruExe]);
    }

    // ── Upload PDF → queue ────────────────────────────────────────────────────
    public function jsonify(Request $request)
    {
        $request->validate([
            'pdf_file'      => 'required|file|mimes:pdf|max:102400',
            'course_year'   => 'required|in:1,2,3',
            'course_branch' => 'nullable|in:mi,st,none',
            'max_attempts'  => 'nullable|integer|min:1|max:10',
            'priority'      => 'nullable|integer|min:1|max:10',
        ]);

        $file     = $request->file('pdf_file');
        $origName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $path     = $file->store('ai_uploads', 'local');

        $user  = Auth::user();
        $aiJob = AiJob::create([
            'status'            => 'queued',
            'pdf_path'          => $path,
            'original_filename' => $origName,
            'file_size'         => $fileSize,
            'year'              => $request->course_year,
            'branch'            => $request->course_branch ?? 'none',
            'max_attempts'      => $request->max_attempts ?? 3,
            'priority'          => $request->priority ?? 5,
            'attempt'           => 0,
            'started_by'        => $user?->name,
            'started_by_id'     => $user?->id,
            'logs'              => [],
        ]);

        ProcessPdfJob::dispatch($aiJob->id);

        return response()->json(['job_id' => $aiJob->id, 'status' => 'queued', 'message' => 'PDF queued.']);
    }

    // ── Recut: re-run only Ollama using markdown saved in logs ────────────────
    // Call: POST /ai/jobs/{id}/recut
    public function recut($id)
    {
        $aiJob = AiJob::findOrFail($id);

        // Extract raw markdown from logs
        $markdown = $this->extractMarkdownFromLogs($aiJob);

        if ($markdown === null) {
            return response()->json([
                'error' => 'No saved markdown found in job logs. '
                    . 'The markdown is stored between __RAW_MARKDOWN_START__ and __RAW_MARKDOWN_END__ log entries. '
                    . 'Re-run the full job to regenerate it.',
            ], 422);
        }

        // Reset job for re-processing (keep attempt count, add to existing logs)
        $existingLogs = $aiJob->logs ?? [];
        $existingLogs[] = [
            'ts'      => now()->format('H:i:s'),
            'level'   => 'info',
            'message' => '--- RECUT triggered by ' . (Auth::user()?->name ?? 'admin') . ' ---',
        ];

        $aiJob->update([
            'status'        => 'queued',
            'error_message' => null,
            'result_json'   => null,
            'finished_at'   => null,
            'logs'          => $existingLogs,
        ]);

        // Dispatch with the saved markdown — skips MinerU entirely
        ProcessPdfJob::dispatch($aiJob->id, $markdown);

        return response()->json([
            'ok'              => true,
            'message'         => 'Recut queued. Ollama will re-process the saved markdown.',
            'markdown_length' => mb_strlen($markdown),
        ]);
    }

    // ── List all jobs (paginated + filterable) ────────────────────────────────
    public function jobsList(Request $request)
    {
        $query = AiJob::query()->orderByDesc('created_at');

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->where('original_filename', 'like', '%' . $request->search . '%');
        }

        $jobs = $query->paginate(20)->through(function ($j) {
            return $this->jobSummary($j);
        });

        return response()->json($jobs);
    }

    // ── Active jobs (for courses page widget) ─────────────────────────────────
    public function activeJobs()
    {
        return response()->json(
            AiJob::whereNotIn('status', ['saved'])
                ->orderBy('created_at', 'desc')
                ->get(['id', 'status', 'original_filename', 'attempt', 'max_attempts', 'created_at', 'updated_at'])
        );
    }

    // ── Single job detail ─────────────────────────────────────────────────────
    public function jobDetail($id)
    {
        $aiJob = AiJob::findOrFail($id);
        return response()->json($this->jobSummary($aiJob, true));
    }

    // ── Poll status ───────────────────────────────────────────────────────────
    public function status($id)
    {
        $aiJob = AiJob::findOrFail($id);
        $data  = ['id' => $aiJob->id, 'status' => $aiJob->status, 'created_at' => $aiJob->created_at, 'updated_at' => $aiJob->updated_at];
        if ($aiJob->status === 'done')   $data['result'] = json_decode($aiJob->result_json, true);
        if ($aiJob->status === 'failed') $data['error']  = $aiJob->error_message;
        return response()->json($data);
    }

    // ── Job logs ──────────────────────────────────────────────────────────────
    public function logs($id)
    {
        $aiJob = AiJob::findOrFail($id);

        // Filter out the raw markdown blob from the logs sent to the UI
        // (it would flood the panel — the recut endpoint retrieves it directly)
        $filteredLogs = array_values(array_filter($aiJob->logs ?? [], function ($entry) {
            $msg = $entry['message'] ?? '';
            if ($entry['level'] === 'raw') return false;
            if ($msg === '__RAW_MARKDOWN_START__') return false;
            if ($msg === '__RAW_MARKDOWN_END__') return false;
            return true;
        }));

        // Tell the UI whether recut is available
        $hasMarkdown = $this->extractMarkdownFromLogs($aiJob) !== null;

        return response()->json([
            'id'              => $aiJob->id,
            'status'          => $aiJob->status,
            'logs'            => $filteredLogs,
            'error'           => $aiJob->error_message,
            'progress'        => $aiJob->progressPercent(),
            'can_recut'       => $hasMarkdown,
        ]);
    }

    // ── Retry a failed job ────────────────────────────────────────────────────
    public function retry($id)
    {
        $aiJob = AiJob::findOrFail($id);

        if ($aiJob->status !== 'failed') {
            return response()->json(['error' => 'Only failed jobs can be retried.'], 422);
        }

        $aiJob->update([
            'status'        => 'queued',
            'error_message' => null,
            'logs'          => array_merge($aiJob->logs ?? [], [[
                'ts' => now()->format('H:i:s'), 'level' => 'info',
                'message' => 'Manually re-queued by ' . (Auth::user()?->name ?? 'admin') . '.',
            ]]),
        ]);

        ProcessPdfJob::dispatch($aiJob->id);

        return response()->json(['ok' => true, 'message' => 'Job re-queued.']);
    }

    // ── Cancel a job ─────────────────────────────────────────────────────────
    public function cancel($id)
    {
        $aiJob = AiJob::findOrFail($id);

        if (!in_array($aiJob->status, ['queued', 'failed'])) {
            return response()->json(['error' => 'Only queued or failed jobs can be cancelled.'], 422);
        }

        $aiJob->log('Cancelled by ' . (Auth::user()?->name ?? 'admin') . '.', 'warn');
        $aiJob->update(['status' => 'cancelled', 'finished_at' => now()]);

        return response()->json(['ok' => true]);
    }

    // ── Delete a job ──────────────────────────────────────────────────────────
    public function deleteJob($id)
    {
        $aiJob = AiJob::findOrFail($id);

        if ($aiJob->pdf_path && Storage::disk('local')->exists($aiJob->pdf_path)) {
            Storage::disk('local')->delete($aiJob->pdf_path);
        }

        $aiJob->delete();
        return response()->json(['ok' => true]);
    }

    // ── Update job meta (note, max_attempts, priority) ────────────────────────
    public function updateJob(Request $request, $id)
    {
        $aiJob     = AiJob::findOrFail($id);
        $validated = $request->validate([
            'note'         => 'nullable|string|max:500',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'priority'     => 'nullable|integer|min:1|max:10',
            'year'         => 'nullable|in:1,2,3',
            'branch'       => 'nullable|in:mi,st,none',
        ]);

        $aiJob->update(array_filter($validated, fn($v) => $v !== null));
        $aiJob->log('Job meta updated by ' . (Auth::user()?->name ?? 'admin') . '.', 'info');

        return response()->json(['ok' => true, 'job' => $this->jobSummary($aiJob)]);
    }

    // ── Clear logs ────────────────────────────────────────────────────────────
    public function clearLogs($id)
    {
        $aiJob = AiJob::findOrFail($id);
        $aiJob->update(['logs' => []]);
        return response()->json(['ok' => true]);
    }

    // ── Bulk actions ──────────────────────────────────────────────────────────
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:retry,cancel,delete',
            'ids'    => 'required|array',
            'ids.*'  => 'integer|exists:ai_jobs,id',
        ]);

        $results = [];
        foreach ($request->ids as $id) {
            try {
                match ($request->action) {
                    'retry'  => $this->retry($id),
                    'cancel' => $this->cancel($id),
                    'delete' => $this->deleteJob($id),
                };
                $results[$id] = 'ok';
            } catch (\Throwable $e) {
                $results[$id] = $e->getMessage();
            }
        }

        return response()->json(['ok' => true, 'results' => $results]);
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
        if (!$data) return response()->json(['error' => 'Result JSON is invalid.'], 422);

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

                    // Normalise list/table/separator content: must be JSON string
                    if (in_array($type, ['list', 'table', 'separator', 'graph', 'function'])) {
                        if (!is_string($content) || json_decode($content) === null) {
                            // Try to encode if already array
                            $raw = $bData['content'];
                            $content = is_array($raw) ? json_encode($raw, JSON_UNESCAPED_UNICODE) : json_encode(['raw' => $content]);
                        }
                    }

                    $blk = block::create([
                        'lesson_id'    => $lessonRecord->id,
                        'type'         => $type,
                        'content'      => $content,
                        'block_number' => $bData['block_number'] ?? ($bIdx + 1),
                    ]);

                    if ($type === 'exercise') {
                        $blk->solutions()->create(['solution_number' => 1, 'content' => 'nothing here yet']);
                    }
                }
            }
        }

        $aiJob->update(['status' => 'saved']);
        $aiJob->log('Saved as course ID ' . $courseRecord->id . ' by ' . (Auth::user()?->name ?? 'admin') . '.', 'ok');

        return response()->json(['success' => true, 'course_id' => $courseRecord->id, 'message' => 'Course saved as draft.']);
    }

    // ── Convert block ─────────────────────────────────────────────────────────
    public function convertBlock(Request $request)
    {
        $request->validate([
            'block_id'    => 'required|exists:blocks,id',
            'target_type' => 'required|in:header,description,note,exercise,code,math,graph,table,function,list,separator,ext,photo,video,markdown',
        ]);
        $blk        = block::findOrFail($request->block_id);
        $targetType = trim($request->target_type);
        $newContent = $this->convertContent($blk->content, $targetType);
        $blk->update(['type' => $targetType, 'content' => $newContent]);
        if ($targetType === 'exercise' && $blk->solutions()->count() === 0) {
            $blk->solutions()->create(['solution_number' => 1, 'content' => 'nothing here yet']);
        }
        return response()->json(['success' => true, 'block' => $blk->fresh()]);
    }

    // ── Stats endpoint ────────────────────────────────────────────────────────
    public function stats()
    {
        return response()->json([
            'total'           => AiJob::count(),
            'queued'          => AiJob::where('status', 'queued')->count(),
            'processing'      => AiJob::where('status', 'processing')->count(),
            'done'            => AiJob::where('status', 'done')->count(),
            'saved'           => AiJob::where('status', 'saved')->count(),
            'failed'          => AiJob::where('status', 'failed')->count(),
            'cancelled'       => AiJob::where('status', 'cancelled')->count(),
            'avg_duration'    => AiJob::whereNotNull('duration_seconds')->avg('duration_seconds'),
        ]);
    }

    // ── Helper: extract raw markdown from job logs ────────────────────────────
    private function extractMarkdownFromLogs(AiJob $aiJob): ?string
    {
        $logs = $aiJob->logs ?? [];
        $capturing = false;
        $lines     = [];

        foreach ($logs as $entry) {
            $msg   = $entry['message'] ?? '';
            $level = $entry['level']   ?? '';

            if ($msg === '__RAW_MARKDOWN_START__') {
                $capturing = true;
                $lines     = [];
                continue;
            }

            if ($msg === '__RAW_MARKDOWN_END__') {
                // Found a complete block — keep it (last one wins in case of retries)
                if (!empty($lines)) {
                    $capturing = false;
                }
                continue;
            }

            if ($capturing && $level === 'raw') {
                $lines[] = $msg;
            }
        }

        if (empty($lines)) {
            return null;
        }

        return implode('', $lines);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function jobSummary(AiJob $job, bool $includeLogs = false): array
    {
        $data = [
            'id'                => $job->id,
            'status'            => $job->status,
            'original_filename' => $job->original_filename,
            'file_size'         => $job->file_size,
            'file_size_human'   => $job->fileSizeHuman(),
            'pdf_path'          => $job->pdf_path,
            'year'              => $job->year,
            'branch'            => $job->branch,
            'attempt'           => $job->attempt,
            'max_attempts'      => $job->max_attempts,
            'priority'          => $job->priority,
            'started_by'        => $job->started_by,
            'started_by_id'     => $job->started_by_id,
            'note'              => $job->note,
            'error_message'     => $job->error_message,
            'progress'          => $job->progressPercent(),
            'can_retry'         => $job->canRetry(),
            'can_cancel'        => $job->canCancel(),
            'can_recut'         => $this->extractMarkdownFromLogs($job) !== null,
            'started_at'        => $job->started_at?->toDateTimeString(),
            'finished_at'       => $job->finished_at?->toDateTimeString(),
            'duration_seconds'  => $job->duration_seconds,
            'created_at'        => $job->created_at?->toDateTimeString(),
            'updated_at'        => $job->updated_at?->toDateTimeString(),
        ];

        if ($includeLogs) {
            $data['logs'] = $job->logs ?? [];
        }

        return $data;
    }

    private function convertContent(string $raw, string $targetType): string
    {
        $plain = trim(strip_tags(preg_replace(['/#{1,6}\s+/', '/\*\*(.+?)\*\*/', '/\*(.+?)\*/'], ['', '$1', '$1'], $raw)));
        return match ($targetType) {
            'header','description','note','code','math','exercise','markdown','ext' => $raw,
            'table'    => json_encode([['Column 1','Column 2'],[$plain,'']], JSON_UNESCAPED_UNICODE),
            'graph'    => json_encode(['type'=>'line','labels'=>['A','B','C'],'data'=>[0,0,0]], JSON_UNESCAPED_UNICODE),
            'function' => json_encode(['function'=>'sin(x)','x_min'=>-10,'x_max'=>10,'y_min'=>-5,'y_max'=>5,'color'=>'#4f46e5','step'=>0.1], JSON_UNESCAPED_UNICODE),
            'list'     => json_encode(['style'=>'bullet','items'=>array_values(array_filter(array_map('trim', preg_split('/\n|,/', $plain))))], JSON_UNESCAPED_UNICODE),
            'separator'=> json_encode(['type'=>'divider']),
            'photo','video' => '',
            default    => $raw,
        };
    }

    private function resolvePython(): string
    {
        $win  = base_path('scripts/.venv/Scripts/python.exe');
        $unix = base_path('scripts/.venv/bin/python3');
        if (file_exists($win))  return $win;
        if (file_exists($unix)) return $unix;
        return PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
    }

    private function pythonEnv(): array
    {
        $env = array_merge($_SERVER, $_ENV);
        foreach (array_keys($env) as $key) {
            if (str_starts_with($key, 'HTTP_') || in_array($key, ['argc','argv','REQUEST_URI','SCRIPT_NAME','SCRIPT_FILENAME','QUERY_STRING'])) {
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
