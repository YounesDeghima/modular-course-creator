<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPdfJob;
use App\Models\AiJob;
use App\Models\course;
use App\Models\chapter;
use App\Models\lesson;
use App\Models\block;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AIController extends Controller
{
    /**
     * ── Test endpoint ──────────────────────────────────────────────────
     * POST /admin/ai/test
     * Sends a simple "hi" prompt to local Ollama and returns the reply.
     */
    public function test(Request $request)
    {
        try {
            $response = Http::timeout(30)->post('http://localhost:11434/api/generate', [
                'model'  => 'phi4',
                'prompt' => 'Say exactly: {"status":"ok","message":"Ollama is running"}',
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

    /**
     * ── Upload PDF → dispatch job ──────────────────────────────────────
     * POST /admin/ai/jsonify
     * Stores the PDF, creates an AiJob record, dispatches the background job.
     * Returns the job ID so the front-end can poll for status.
     */
    public function jsonify(Request $request)
    {
        $request->validate([
            'pdf_file'    => 'required|file|mimes:pdf|max:51200', // 50 MB
            'course_year' => 'required|in:1,2,3',
            'course_branch' => 'nullable|in:mi,st,none',
        ]);

        // Store the PDF
        $path = $request->file('pdf_file')->store('ai_uploads', 'local');

        // Create a job record so we can track progress
        $aiJob = AiJob::create([
            'status'   => 'queued',
            'pdf_path' => $path,
            'year'     => $request->course_year,
            'branch'   => $request->course_branch ?? 'none',
        ]);

        // Dispatch background job — no HTTP timeout risk
        ProcessPdfJob::dispatch($aiJob->id);

        return response()->json([
            'job_id'  => $aiJob->id,
            'status'  => 'queued',
            'message' => 'PDF queued for processing. Poll /admin/ai/status/{id} for updates.',
        ]);
    }

    public function activeJobs()
    {
        $jobs = AiJob::whereNotIn('status', ['saved'])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'status', 'created_at', 'updated_at']);

        return response()->json($jobs);
    }

    /**
     * ── Poll job status ────────────────────────────────────────────────
     * GET /admin/ai/status/{id}
     * Returns the current status + result JSON when done.
     */
    public function status($id)
    {
        $aiJob = AiJob::findOrFail($id);

        $response = [
            'id'         => $aiJob->id,
            'status'     => $aiJob->status,       // queued | processing | done | failed
            'created_at' => $aiJob->created_at,
            'updated_at' => $aiJob->updated_at,
        ];

        if ($aiJob->status === 'done') {
            $response['result'] = json_decode($aiJob->result_json, true);
        }

        if ($aiJob->status === 'failed') {
            $response['error'] = $aiJob->error_message;
        }

        return response()->json($response);
    }

    /**
     * ── Save result JSON to the database ──────────────────────────────
     * POST /admin/ai/store
     * Takes the JSON produced by the AI and persists it as a real Course
     * with its Chapters, Lessons and Blocks — exactly like the existing
     * controllers expect.
     */
    public function store(Request $request)
    {
        $request->validate([
            'job_id' => 'required|exists:ai_jobs,id',
        ]);

        $aiJob = AiJob::findOrFail($request->job_id);

        if ($aiJob->status !== 'done') {
            return response()->json(['error' => 'Job is not finished yet.'], 422);
        }

        $data = json_decode($aiJob->result_json, true);

        if (!$data) {
            return response()->json(['error' => 'Result JSON is invalid.'], 422);
        }

        // ── Create Course ──────────────────────────────────────────────
        $courseRecord = course::create([
            'title'       => $data['title']       ?? 'Untitled Course',
            'year'        => $data['year']         ?? $aiJob->year,
            'branch'      => $data['branch']       ?? $aiJob->branch,
            'description' => $data['description']  ?? '',
            'status'      => 'draft',               // always start as draft
        ]);

        foreach (($data['chapters'] ?? []) as $chIdx => $chData) {
            // ── Create Chapter ─────────────────────────────────────────
            $chapterRecord = chapter::create([
                'course_id'      => $courseRecord->id,
                'title'          => $chData['title']          ?? 'Chapter ' . ($chIdx + 1),
                'description'    => $chData['description']    ?? '',
                'chapter_number' => $chData['chapter_number'] ?? ($chIdx + 1),
                'status'         => 'draft',
            ]);

            foreach (($chData['lessons'] ?? []) as $lIdx => $lData) {
                // ── Create Lesson ──────────────────────────────────────
                $lessonRecord = lesson::create([
                    'chapter_id'    => $chapterRecord->id,
                    'title'         => $lData['title']         ?? 'Lesson ' . ($lIdx + 1),
                    'description'   => $lData['description']   ?? '',
                    'lesson_number' => $lData['lesson_number'] ?? ($lIdx + 1),
                    'content'       => '',
                    'status'        => 'draft',
                ]);

                foreach (($lData['blocks'] ?? []) as $bIdx => $bData) {
                    // ── Create Block ───────────────────────────────────
                    $type    = $bData['type']    ?? 'description';
                    $content = $bData['content'] ?? '';

                    // Normalize content based on block type
                    $content = $this->normalizeBlockContent($type, $content);

                    block::create([
                        'lesson_id'    => $lessonRecord->id,
                        'type'         => $type,
                        'content'      => $content,
                        'block_number' => $bData['block_number'] ?? ($bIdx + 1),
                    ]);

                    // Auto-create a placeholder solution for exercise blocks
                    if ($type === 'exercise') {
                        $block = block::where('lesson_id', $lessonRecord->id)
                            ->orderBy('id', 'desc')
                            ->first();
                        if ($block) {
                            $block->solutions()->create([
                                'solution_number' => 1,
                                'content'         => 'nothing here yet',
                            ]);
                        }
                    }
                }
            }
        }

        // Mark job as consumed
        $aiJob->update(['status' => 'saved']);

        return response()->json([
            'success'   => true,
            'course_id' => $courseRecord->id,
            'message'   => 'Course saved to database as draft.',
        ]);
    }

    /**
     * Normalize block content based on type
     * Ensures JSON-encoded types have valid JSON strings
     */
    private function normalizeBlockContent(string $type, $content): string
    {
        // Types that MUST be valid JSON
        $jsonTypes = ['table', 'graph', 'function', 'list', 'separator'];

        if (!in_array($type, $jsonTypes)) {
            // Plain text types — return as-is
            return is_string($content) ? $content : '';
        }

        // Already a string — try to parse and re-encode to ensure validity
        if (is_string($content)) {
            $decoded = json_decode($content, true);

            // If valid JSON, re-encode to normalize (prevents double-encoding)
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }

            // Invalid JSON string — try to fix or return default
            return $this->getDefaultJsonContent($type);
        }

        // Array or object — encode to JSON
        if (is_array($content) || is_object($content)) {
            return json_encode($content, JSON_UNESCAPED_UNICODE);
        }

        // Fallback for unexpected types
        return $this->getDefaultJsonContent($type);
    }

    /**
     * Get default JSON content for each structured type
     */
    private function getDefaultJsonContent(string $type): string
    {
        $defaults = [
            'table' => json_encode([['Column 1', 'Column 2'], ['Data 1', 'Data 2']]),
            'graph' => json_encode(['type' => 'line', 'labels' => [], 'data' => []]),
            'function' => json_encode([
                'function' => 'sin(x)',
                'x_min' => -10,
                'x_max' => 10,
                'y_min' => -5,
                'y_max' => 5,
                'color' => '#4f46e5',
                'step' => 0.1
            ]),
            'list' => json_encode(['style' => 'bullet', 'items' => []]),
            'separator' => json_encode(['type' => 'divider']),
        ];

        return $defaults[$type] ?? json_encode([]);
    }

}
