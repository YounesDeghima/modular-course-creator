<?php

namespace App\Http\Controllers;

use App\Models\block;
use App\Models\chapter;
use App\Models\course;
use App\Models\lesson;
use App\Models\exercisesolution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\lesson_progress;


class blockcontroller extends Controller
{
    public function index(Course $course, Chapter $chapter, Lesson $lesson)
    {
        $blocks = $lesson->blocks()
            ->with('solutions')
            ->orderBy('block_number', 'asc')
            ->get();

        $block_count = $blocks->count();
        $chapters = $course->chapters()->with('lessons')->get();
        $chapter_count = $chapters->count();

        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        if (request()->ajax()) {
            return view('pages.admin.chapters', compact(
                'blocks', 'course', 'chapter', 'lesson',
                'block_count', 'chapters', 'chapter_count',
                'id', 'name', 'email'
            ))->fragment('main-content');
        }

        return view('pages.admin.chapters', compact(
            'blocks', 'block_count', 'course', 'chapter', 'lesson',
            'chapters', 'chapter_count', 'admin', 'id', 'name', 'email'
        ));
    }

    /**
     * Dedicated media upload endpoint.
     * Called immediately when user picks a file — returns the stored path.
     * The bulk-save form then just saves the path as plain text (no file transfer needed).
     */
    public function uploadMedia(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:204800', // 200 MB max
            'type' => 'required|in:photo,video',
        ]);

        $file = $request->file('file');

        if (!$file->isValid()) {
            return response()->json(['error' => 'Invalid file'], 422);
        }

        $path = $file->store('blocks', 'public');

        return response()->json(['path' => $path]);
    }

    public function updateAll(Request $request, $courseId, $chapterId, $lessonId)
    {
        // 1. HANDLE REORDERING
        if ($request->has('move')) {
            $parts = explode(':', $request->move);
            $currentBlockId = $parts[0];
            $direction = $parts[1];

            $currentBlock = block::findOrFail($currentBlockId);
            $currentOrder = $currentBlock->block_number;

            if ($direction === 'up') {
                $previousBlock = block::where('lesson_id', $lessonId)
                    ->where('block_number', '<', $currentOrder)
                    ->orderBy('block_number', 'desc')
                    ->first();

                if ($previousBlock) {
                    $currentBlock->update(['block_number' => $previousBlock->block_number]);
                    $previousBlock->update(['block_number' => $currentOrder]);
                }
            } else {
                $nextBlock = block::where('lesson_id', $lessonId)
                    ->where('block_number', '>', $currentOrder)
                    ->orderBy('block_number', 'asc')
                    ->first();

                if ($nextBlock) {
                    $currentBlock->update(['block_number' => $nextBlock->block_number]);
                    $nextBlock->update(['block_number' => $currentOrder]);
                }
            }
        }

        // 2. BULK UPDATE — files are already uploaded, content field has the path
        if ($request->has('blocks')) {
            foreach ($request->blocks as $id => $data) {
                $block = block::find($id);
                if (!$block) continue;

                $type = $data['type'] ?? $block->type;
                $content = '';

                if ($type === 'table') {
                    $content = json_encode($data['table_data'] ?? json_decode($block->content, true) ?? []);
                } elseif ($type === 'function') {
                    $content = json_encode([
                        'function' => $data['func_expression'] ?? 'sin(x)',
                        'x_min'    => floatval($data['x_min'] ?? -10),
                        'x_max'    => floatval($data['x_max'] ?? 10),
                        'y_min'    => floatval($data['y_min'] ?? -5),
                        'y_max'    => floatval($data['y_max'] ?? 5),
                        'color'    => $data['color'] ?? '#4f46e5',
                        'step'     => floatval($data['step'] ?? 0.1),
                    ]);
                } elseif ($type === 'graph') {
                    $lines  = explode("\n", $data['chart_data'] ?? '');
                    $labels = isset($lines[0]) ? array_map('trim', explode(',', $lines[0])) : [];
                    $values = isset($lines[1]) ? array_map('trim', explode(',', $lines[1])) : [];
                    $content = json_encode([
                        'type'   => $data['chart_type'] ?? 'line',
                        'labels' => $labels,
                        'data'   => $values,
                    ]);
                } elseif ($type === 'list') {
                    $items = array_filter(array_map('trim', explode("\n", $data['list_items'] ?? '')));
                    $content = json_encode([
                        'style' => $data['list_style'] ?? 'bullet',
                        'items' => $items,
                    ]);
                } elseif ($type === 'separator') {
                    $content = json_encode([
                        'type' => $data['separator_type'] ?? 'divider',
                    ]);
                } elseif ($type === 'code') {

                    $raw     = trim($data['content'] ?? '');
                    $decoded = json_decode($raw, true);

                    if (is_array($decoded) && isset($decoded['code'])) {
                        $content = $raw; // proper JSON, store as-is
                    } else {
                        // legacy — wrap it
                        $content = json_encode([
                            'mode'       => 'free',
                            'language'   => '',
                            'version'    => '',
                            'code'       => $raw,
                            'problem'    => '',
                            'test_cases' => [],
                        ]);
                    }

                } else {
                    $content = trim($data['content'] ?? '');
                }


                // Delete empty text blocks
                if ($content === '' && !in_array($type, ['photo', 'video', 'exercise'])) {
                    $block->delete();
                    continue;
                }

                $block->update([
                    'content'      => $content,
                    'type'         => $type,
                    'block_number' => $data['block_number'] ?? $block->block_number,
                ]);

                // Handle exercise solutions
                if ($type === 'exercise') {
                    if ($block->solutions()->count() === 0) {
                        $block->solutions()->create([
                            'solution_number' => 1,
                            'content'         => 'nothing here yet',
                        ]);
                    }

                    if (isset($data['solutions'])) {
                        foreach ($data['solutions'] as $solutionId => $solContent) {
                            if (is_numeric($solutionId)) {
                                $solution = $block->solutions()->find($solutionId);
                                if ($solution) {
                                    $solution->update(['content' => $solContent]);
                                }
                            } else {
                                if (is_array($solContent)) {
                                    foreach ($solContent as $newContent) {
                                        if (trim($newContent) !== '') {
                                            $block->solutions()->create([
                                                'content'         => $newContent,
                                                'solution_number' => $block->solutions()->count() + 1,
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function store(Request $request, Course $course, Chapter $chapter, Lesson $lesson)
    {
        $validated = $request->validate([
            'type'         => 'required|in:header,description,note,exercise,
                                code,photo,video,math,graph,table,ext,function,
                                list,separator,markdown',   // ← markdown added
            'block_number' => 'required|integer',
        ]);

        $validated['lesson_id'] = $lesson->id;

        if (in_array($request->type, ['photo', 'video'])) {
            $validated['content'] = '';
        } elseif ($request->type === 'table') {
            $validated['content'] = json_encode($request->input('table_data', [['Column 1', 'Column 2'], ['Row 1', 'Data']]));
        } elseif ($request->type === 'function') {
            $validated['content'] = json_encode([
                'function' => $request->input('func_expression', 'sin(x)'),
                'x_min'    => floatval($request->input('x_min', -10)),
                'x_max'    => floatval($request->input('x_max', 10)),
                'y_min'    => floatval($request->input('y_min', -5)),
                'y_max'    => floatval($request->input('y_max', 5)),
                'color'    => $request->input('func_color', '#4f46e5'),
                'step'     => 0.1,
            ]);
        } elseif ($request->type === 'graph') {
            $lines  = explode("\n", $request->input('chart_data', "Jan,Feb,Mar\n10,20,15"));
            $labels = isset($lines[0]) ? array_map('trim', explode(',', $lines[0])) : [];
            $values = isset($lines[1]) ? array_map('trim', explode(',', $lines[1])) : [];
            $validated['content'] = json_encode([
                'type'   => $request->input('chart_type', 'line'),
                'labels' => $labels,
                'data'   => $values,
            ]);
        } elseif ($request->type === 'list') {
            $validated['content'] = json_encode([
                'style' => $request->input('list_style', 'bullet'),
                'items' => array_filter(array_map('trim', explode("\n", $request->input('list_items', '')))),
            ]);
        } elseif ($request->type === 'separator') {
            $validated['content'] = json_encode([
                'type' => $request->input('separator_type', 'divider'),
            ]);
        } elseif ($request->type === 'code') {

            // New JSON schema — if frontend sends JSON content, store as-is.
            // If it sends old plain text, wrap it.
            $raw = $request->input('content', '');
            $decoded = json_decode($raw, true);

            if (is_array($decoded) && isset($decoded['code'])) {
                // Already proper JSON from the upgraded block editor
                $validated['content'] = $raw;
            } else {
                // Legacy plain code — migrate to new schema
                $validated['content'] = json_encode([
                    'mode'       => 'free',
                    'language'   => 'python',
                    'version'    => '',
                    'code'       => $raw,
                    'problem'    => '',
                    'test_cases' => [],
                ]);
            }

        } else {
            // Covers: header, description, note, exercise, code, math, ext, markdown
            $validated['content'] = $request->input('content', '');
        }

        $block = block::create($validated);

        if ($block->type === 'exercise') {
            $block->solutions()->create([
                'solution_number' => 1,
                'block_id'        => $block->id,
                'content'         => 'nothing here yet',
            ]);
        }

        return response()->json(['block' => $block], 201);
    }

    public function show(string $id) {}
    public function edit(string $id) {}
    public function create() {}

    public function update(Request $request, course $course, chapter $chapter, lesson $lesson, block $block)
    {
        $direction = $request->input('update');

        if ($direction === 'up') {
            $previous = block::where('lesson_id', $lesson->id)
                ->where('block_number', '<', $block->block_number)
                ->orderBy('block_number', 'desc')->first();

            if ($previous) {
                $temp = $block->block_number;
                $block->block_number = $previous->block_number;
                $previous->block_number = $temp;
                $block->save();
                $previous->save();
            }
            return back();
        }

        if ($direction === 'down') {
            $next = block::where('lesson_id', $lesson->id)
                ->where('block_number', '>', $block->block_number)
                ->orderBy('block_number', 'asc')->first();

            if ($next) {
                $temp = $block->block_number;
                $block->block_number = $next->block_number;
                $next->block_number = $temp;
                $block->save();
                $next->save();
            }
            return back();
        }

        $validated = $request->validate([
            'type'         => 'required|in:header,description,note,exercise,code,photo,video,math,graph,table,ext,function,list,separator,markdown',
            'block_number' => 'required|integer',
            'content'      => 'nullable|string',

        ]);

        if ($block->type === 'exercise') {
            foreach ($block->solutions as $solution) {
                $solution->content = $request->input('solution');
                $solution->save();
            }
        }

        $block->update($validated);
        return redirect()->back()->with('success', 'block updated');
    }

    public function destroy(course $course, chapter $chapter, lesson $lesson, block $block)
    {
        $block = block::findOrFail($block->id);
        $block->delete();
        return redirect()->back()->with('success', 'block deleted');
    }
}
