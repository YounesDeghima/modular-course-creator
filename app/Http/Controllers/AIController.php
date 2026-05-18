<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPdfJob;
use App\Models\AiJob;
use App\Models\AiJobSnapshot;
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

    // ── Detect available Ollama models ────────────────────────────────────────
    public function models()
    {
        try {
            $response = Http::timeout(5)->get('http://localhost:11434/api/tags');
            if ($response->failed()) {
                return response()->json(['ok' => false, 'models' => [], 'error' => 'Ollama not reachable.']);
            }
            $models = collect($response->json('models') ?? [])
                ->pluck('name')
                ->values()
                ->all();
            return response()->json(['ok' => true, 'models' => $models]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'models' => [], 'error' => $e->getMessage()]);
        }
    }

    // ── Test Ollama (uses detected or specified model) ────────────────────────
    public function test(Request $request)
    {
        $model = $request->input('model', 'phi4');
        try {
            $response = Http::timeout(30)->post('http://localhost:11434/api/generate', [
                'model'  => $model,
                'prompt' => 'Reply with exactly: {"status":"ok","message":"' . $model . ' is running"}',
                'stream' => false,
                'format' => 'json',
            ]);
            if ($response->failed()) {
                return response()->json(['error' => "Ollama [{$model}] did not respond. Is it running on port 11434?"], 502);
            }
            return response()->json(['ok' => true, 'message' => "Ollama [{$model}] is reachable!", 'raw' => $response->json()]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ── Chat with Ollama (test tab) ───────────────────────────────────────────
    public function chat(Request $request)
    {
        $request->validate([
            'model'    => 'required|string',
            'messages' => 'required|array',
        ]);

        try {
            $response = Http::timeout(120)->post('http://localhost:11434/api/chat', [
                'model'    => $request->model,
                'messages' => $request->messages,  // [{role: user|assistant, content: "..."}]
                'stream'   => false,
            ]);

            if ($response->failed()) {
                return response()->json(['error' => 'Ollama error: ' . $response->status()], 502);
            }

            $content = $response->json('message.content') ?? '';
            return response()->json(['ok' => true, 'content' => $content]);
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
            'model'         => 'nullable|string|max:100',
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
            'model'             => $request->model ?? 'phi4',
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

    // ── Retry from scratch (full MinerU + Ollama) ─────────────────────────────
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
                'message' => 'Full retry by ' . (Auth::user()?->name ?? 'admin') . '.',
            ]]),
        ]);

        ProcessPdfJob::dispatch($aiJob->id);

        return response()->json(['ok' => true, 'message' => 'Full retry queued (new MinerU + Ollama).']);
    }

    // ── Retry MinerU only on a snapshot (re-extract PDF, new md snapshot) ─────
    public function retryMd(Request $request, $id)
    {
        $aiJob = AiJob::findOrFail($id);

        $aiJob->log('Retry MinerU triggered by ' . (Auth::user()?->name ?? 'admin') . '.', 'info');
        $aiJob->update(['status' => 'queued', 'error_message' => null]);

        // Dispatch full job — it will create a new snapshot with the next md_index
        ProcessPdfJob::dispatch($aiJob->id);

        return response()->json(['ok' => true, 'message' => 'MinerU re-extract queued. New snapshot will appear.']);
    }

    // ── Retry Ollama only on a specific snapshot ──────────────────────────────
    public function recutSnapshot(Request $request, $jobId, $snapshotId)
    {
        $aiJob    = AiJob::findOrFail($jobId);
        $snapshot = AiJobSnapshot::where('ai_job_id', $jobId)->where('id', $snapshotId)->firstOrFail();

        $model = $request->input('model', $aiJob->model ?? 'phi4');

        if (empty($snapshot->markdown)) {
            return response()->json(['error' => 'Snapshot has no markdown. Re-run MinerU first.'], 422);
        }

        $aiJob->log("Recut snapshot #{$snapshot->md_index} with [{$model}] by " . (Auth::user()?->name ?? 'admin') . '.', 'info');
        $aiJob->update(['status' => 'queued', 'error_message' => null]);

        ProcessPdfJob::dispatch($aiJob->id, $snapshot->id, $model);

        return response()->json(['ok' => true, 'message' => "Recut queued on snapshot #{$snapshot->md_index} with [{$model}]."]);
    }

    // ── List snapshots for a job ──────────────────────────────────────────────
    public function snapshots($id)
    {
        $aiJob     = AiJob::findOrFail($id);
        $snapshots = AiJobSnapshot::where('ai_job_id', $id)->orderBy('md_index')->get();

        return response()->json([
            'job_id'    => $id,
            'snapshots' => $snapshots->map(fn($s) => $this->snapshotSummary($s))->all(),
        ]);
    }

    // ── Save a specific result as course ──────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'job_id'      => 'required|exists:ai_jobs,id',
            'snapshot_id' => 'nullable|exists:ai_job_snapshots,id',
            'result_index'=> 'nullable|integer|min:1',
        ]);

        $aiJob = AiJob::findOrFail($request->job_id);

        // Determine which result JSON to use
        $resultJson = null;

        if ($request->snapshot_id && $request->result_index) {
            $snapshot = AiJobSnapshot::findOrFail($request->snapshot_id);
            $results  = $snapshot->results ?? [];
            $entry    = collect($results)->firstWhere('index', $request->result_index);
            if ($entry && $entry['status'] === 'done') {
                $resultJson = $entry['result_json'];
            }
        }

        // Fallback to job's result_json
        if (!$resultJson) {
            if ($aiJob->status !== 'done' && $aiJob->status !== 'saved') {
                return response()->json(['error' => 'No successful result available.'], 422);
            }
            $resultJson = $aiJob->result_json;
        }

        $data = json_decode($resultJson, true);
        if (!$data) return response()->json(['error' => 'Result JSON is invalid.'], 422);

        try {
            \DB::beginTransaction();

            // Helper: safely truncate strings to fit DB columns
            $t = fn($s, $max = 255) => mb_substr((string)($s ?? ''), 0, $max);

            $courseRecord = course::create([
                'title'       => $t($data['title'] ?? 'Untitled Course'),
                'year'        => $data['year']   ?? $aiJob->year,
                'branch'      => $data['branch'] ?? $aiJob->branch,
                'description' => $t($data['description'] ?? '', 1000),
                'status'      => 'draft',
            ]);

            foreach (($data['chapters'] ?? []) as $chIdx => $chData) {
                $chapterRecord = chapter::create([
                    'course_id'      => $courseRecord->id,
                    'title'          => $t($chData['title'] ?? 'Chapter ' . ($chIdx + 1)),
                    'description'    => $t($chData['description'] ?? '', 1000),
                    'chapter_number' => $chData['chapter_number'] ?? ($chIdx + 1),
                    'status'         => 'draft',
                ]);

                foreach (($chData['lessons'] ?? []) as $lIdx => $lData) {
                    $lessonRecord = lesson::create([
                        'chapter_id'    => $chapterRecord->id,
                        'title'         => $t($lData['title'] ?? 'Lesson ' . ($lIdx + 1)),
                        'description'   => $t($lData['description'] ?? '', 1000),
                        'lesson_number' => $lData['lesson_number'] ?? ($lIdx + 1),
                        'content'       => '',
                        'status'        => 'draft',
                    ]);

                    foreach (($lData['blocks'] ?? []) as $bIdx => $bData) {
                        $type = $bData['type'] ?? 'markdown';
                        $raw  = $bData['content'] ?? '';

                        // Always a plain string
                        if (is_array($raw) || is_object($raw)) {
                            $content = json_encode($raw, JSON_UNESCAPED_UNICODE);
                        } else {
                            $content = (string) $raw;
                        }

                        // Structured types: must be valid JSON
                        if (in_array($type, ['list', 'table', 'separator', 'graph', 'function'])) {
                            json_decode($content);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $content = json_encode(['raw' => $content], JSON_UNESCAPED_UNICODE);
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
            $aiJob->log('Saved as course ID ' . $courseRecord->id . '.', 'ok');

            \DB::commit();
            return response()->json(['success' => true, 'course_id' => $courseRecord->id, 'message' => 'Course saved as draft.']);

        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('AI store failed: ' . $e->getMessage(), [
                'job_id' => $aiJob->id,
                'file'   => $e->getFile(),
                'line'   => $e->getLine(),
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'file'  => basename($e->getFile()),
                'line'  => $e->getLine(),
            ], 500);
        }
    }

    // ── List all jobs (paginated) ─────────────────────────────────────────────
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

        $filteredLogs = array_values(array_filter($aiJob->logs ?? [], function ($entry) {
            $level = $entry['level'] ?? '';
            $msg   = $entry['message'] ?? '';
            if ($level === 'raw') return false;
            if ($msg === '__RAW_MARKDOWN_START__' || $msg === '__RAW_MARKDOWN_END__') return false;
            return true;
        }));

        return response()->json([
            'id'       => $aiJob->id,
            'status'   => $aiJob->status,
            'logs'     => $filteredLogs,
            'error'    => $aiJob->error_message,
            'progress' => $aiJob->progressPercent(),
        ]);
    }

    // ── Cancel ────────────────────────────────────────────────────────────────
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

    // ── Delete ────────────────────────────────────────────────────────────────
    public function deleteJob($id)
    {
        $aiJob = AiJob::findOrFail($id);
        if ($aiJob->pdf_path && Storage::disk('local')->exists($aiJob->pdf_path)) {
            Storage::disk('local')->delete($aiJob->pdf_path);
        }
        // Delete image dirs
        $imagesDir = "ai_images/{$id}";
        if (Storage::disk('public')->exists($imagesDir)) {
            Storage::disk('public')->deleteDirectory($imagesDir);
        }
        $aiJob->delete(); // cascades to snapshots
        return response()->json(['ok' => true]);
    }

    // ── Update job meta ───────────────────────────────────────────────────────
    public function updateJob(Request $request, $id)
    {
        $aiJob     = AiJob::findOrFail($id);
        $validated = $request->validate([
            'note'         => 'nullable|string|max:500',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'priority'     => 'nullable|integer|min:1|max:10',
            'year'         => 'nullable|in:1,2,3',
            'branch'       => 'nullable|in:mi,st,none',
            'model'        => 'nullable|string|max:100',
        ]);
        $aiJob->update(array_filter($validated, fn($v) => $v !== null));
        $aiJob->log('Meta updated by ' . (Auth::user()?->name ?? 'admin') . '.', 'info');
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

    // ── Stats ─────────────────────────────────────────────────────────────────
    public function stats()
    {
        return response()->json([
            'total'        => AiJob::count(),
            'queued'       => AiJob::where('status', 'queued')->count(),
            'processing'   => AiJob::where('status', 'processing')->count(),
            'done'         => AiJob::where('status', 'done')->count(),
            'saved'        => AiJob::where('status', 'saved')->count(),
            'failed'       => AiJob::where('status', 'failed')->count(),
            'cancelled'    => AiJob::where('status', 'cancelled')->count(),
            'avg_duration' => AiJob::whereNotNull('duration_seconds')->avg('duration_seconds'),
        ]);
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
            'model'             => $job->model ?? 'phi4',
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
            'started_at'        => $job->started_at?->toDateTimeString(),
            'finished_at'       => $job->finished_at?->toDateTimeString(),
            'duration_seconds'  => $job->duration_seconds,
            'created_at'        => $job->created_at?->toDateTimeString(),
            'updated_at'        => $job->updated_at?->toDateTimeString(),
            'snapshot_count'    => AiJobSnapshot::where('ai_job_id', $job->id)->count(),
        ];

        if ($includeLogs) {
            $data['logs'] = $job->logs ?? [];
        }

        return $data;
    }

    private function snapshotSummary(AiJobSnapshot $s): array
    {
        return [
            'id'              => $s->id,
            'md_index'        => $s->md_index,
            'md_status'       => $s->md_status,
            'md_error'        => $s->md_error,
            'md_created_at'   => $s->md_created_at?->toDateTimeString(),
            'markdown_length' => mb_strlen($s->markdown ?? ''),
            'image_count'     => count($s->image_urls ?? []),
            'image_urls'      => $s->image_urls ?? [],
            'results'         => array_map(function ($r) {
                // Don't send full result_json in list view — too heavy
                return [
                    'index'            => $r['index'],
                    'model'            => $r['model'],
                    'status'           => $r['status'],
                    'error'            => $r['error'] ?? null,
                    'created_at'       => $r['created_at'],
                    'duration_seconds' => $r['duration_seconds'] ?? null,
                    'has_result'       => !empty($r['result_json']),
                ];
            }, $s->results ?? []),
        ];
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
