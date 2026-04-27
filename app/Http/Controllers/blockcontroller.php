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

    /**
     * Explode a single markdown block into typed blocks.
     * Called via POST from the "Explode" button in _blocks_blade.
     */
    public function explodeMarkdown(Request $request, $courseId, $chapterId, $lessonId, $blockId)
    {
        $markdownBlock = block::findOrFail($blockId);

        if ($markdownBlock->lesson_id != $lessonId) {
            return response()->json(['error' => 'Block does not belong to this lesson.'], 403);
        }

        $raw = $markdownBlock->content;

        // Parse markdown into typed segments
        $segments = $this->parseMarkdownToSegments($raw);

        if (empty($segments)) {
            return response()->json(['error' => 'Nothing to explode.'], 422);
        }

        // Shift all existing blocks after this one to make room
        $insertAt = $markdownBlock->block_number;
        $shiftCount = count($segments) - 1; // -1 because the original block is replaced

        if ($shiftCount > 0) {
            block::where('lesson_id', $lessonId)
                ->where('block_number', '>', $insertAt)
                ->orderBy('block_number', 'desc')
                ->get()
                ->each(fn($b) => $b->update(['block_number' => $b->block_number + $shiftCount]));
        }

        // Replace the markdown block with the first segment, then create the rest
        $created = [];
        foreach ($segments as $i => $segment) {
            if ($i === 0) {
                $markdownBlock->update([
                    'type'         => $segment['type'],
                    'content'      => $segment['content'],
                    'block_number' => $insertAt,
                ]);
                if ($segment['type'] === 'exercise') {
                    if ($markdownBlock->solutions()->count() === 0) {
                        $markdownBlock->solutions()->create([
                            'solution_number' => 1,
                            'content'         => 'nothing here yet',
                        ]);
                    }
                }
                $created[] = $markdownBlock->fresh();
            } else {
                $newBlock = block::create([
                    'lesson_id'    => $lessonId,
                    'type'         => $segment['type'],
                    'content'      => $segment['content'],
                    'block_number' => $insertAt + $i,
                ]);
                if ($segment['type'] === 'exercise') {
                    $newBlock->solutions()->create([
                        'solution_number' => 1,
                        'content'         => 'nothing here yet',
                    ]);
                }
                $created[] = $newBlock;
            }
        }

        return response()->json([
            'success' => true,
            'created' => count($created),
            'lesson_id' => $lessonId,
        ]);
    }

    /**
     * Parse a markdown string into an array of ['type'=>..., 'content'=>...] segments.
     *
     * Rules:
     *  # Heading    → header
     *  ## / ###     → header (kept as-is, rendered as smaller)
     *  ```...```    → code
     *  $$...$$      → math (display)
     *  $...$        → stays as description (inline math, not isolated enough)
     *  > quote      → note
     *  ---/***      → separator
     *  |table|      → table (JSON)
     *  ![img]()     → photo (URL in content)
     *  plain lines  → description (merged into paragraphs)
     *  - / * / 1.   → list (JSON)
     */
    private function parseMarkdownToSegments(string $raw): array
    {
        $lines = explode("\n", $raw);
        $segments = [];
        $i = 0;
        $total = count($lines);

        while ($i < $total) {
            $line = $lines[$i];
            $trimmed = rtrim($line);

            // ── Fenced code block ──
            if (preg_match('/^```/', $trimmed)) {
                $code = '';
                $i++;
                while ($i < $total && !preg_match('/^```/', rtrim($lines[$i]))) {
                    $code .= $lines[$i] . "\n";
                    $i++;
                }
                $i++; // skip closing ```
                if (trim($code) !== '') {
                    $segments[] = ['type' => 'code', 'content' => rtrim($code)];
                }
                continue;
            }

            // ── Display math block $$...$$ ──
            if (preg_match('/^\$\$/', $trimmed)) {
                $math = '';
                // Single-line $$...$$ 
                if (preg_match('/^\$\$(.+)\$\$$/', $trimmed, $m)) {
                    $segments[] = ['type' => 'math', 'content' => trim($m[1])];
                    $i++;
                    continue;
                }
                // Multi-line
                $i++;
                while ($i < $total && !preg_match('/^\$\$/', rtrim($lines[$i]))) {
                    $math .= $lines[$i] . "\n";
                    $i++;
                }
                $i++; // skip closing $$
                if (trim($math) !== '') {
                    $segments[] = ['type' => 'math', 'content' => rtrim($math)];
                }
                continue;
            }

            // ── Heading ──
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $m)) {
                $segments[] = ['type' => 'header', 'content' => trim($m[2])];
                $i++;
                continue;
            }

            // ── Horizontal rule (separator) ──
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', $trimmed)) {
                $segments[] = [
                    'type'    => 'separator',
                    'content' => json_encode(['type' => 'divider']),
                ];
                $i++;
                continue;
            }

            // ── Blockquote → note ──
            if (preg_match('/^>\s?(.*)$/', $trimmed, $m)) {
                $noteLines = [trim($m[1])];
                $i++;
                while ($i < $total && preg_match('/^>\s?(.*)$/', rtrim($lines[$i]), $m2)) {
                    $noteLines[] = trim($m2[1]);
                    $i++;
                }
                $segments[] = ['type' => 'note', 'content' => implode("\n", $noteLines)];
                continue;
            }

            // ── Image → photo ──
            if (preg_match('/^!\[.*?\]\((.+?)\)$/', $trimmed, $m)) {
                $segments[] = ['type' => 'photo', 'content' => trim($m[1])];
                $i++;
                continue;
            }

            // ── Table ──
            if (preg_match('/^\|/', $trimmed)) {
                $tableLines = [];
                while ($i < $total && preg_match('/^\|/', rtrim($lines[$i]))) {
                    $tableLines[] = rtrim($lines[$i]);
                    $i++;
                }
                $tableData = $this->parseMarkdownTable($tableLines);
                if (!empty($tableData)) {
                    $segments[] = [
                        'type'    => 'table',
                        'content' => json_encode($tableData),
                    ];
                }
                continue;
            }

            // ── List (bullet or numbered) ──
            if (preg_match('/^(\s*[-*+]|\s*\d+\.)\s+(.+)$/', $trimmed, $m)) {
                $isNumbered = preg_match('/^\s*\d+\./', $trimmed);
                $items = [trim($m[2])];
                $i++;
                while ($i < $total && preg_match('/^(\s*[-*+]|\s*\d+\.)\s+(.+)$/', rtrim($lines[$i]), $m2)) {
                    $items[] = trim($m2[2]);
                    $i++;
                }
                $segments[] = [
                    'type'    => 'list',
                    'content' => json_encode([
                        'style' => $isNumbered ? 'numbered' : 'bullet',
                        'items' => $items,
                    ]),
                ];
                continue;
            }

            // ── Empty line ── skip
            if (trim($trimmed) === '') {
                $i++;
                continue;
            }

            // ── Plain paragraph — gather until blank line ──
            $paraLines = [$trimmed];
            $i++;
            while ($i < $total) {
                $next = rtrim($lines[$i]);
                if ($next === '') break;
                // Stop if next line starts a special block
                if (preg_match('/^(#{1,6}\s|```|\$\$|>|!\[|-{3,}|\*{3,}|\||\s*[-*+]\s|\s*\d+\.\s)/', $next)) break;
                $paraLines[] = $next;
                $i++;
            }
            $segments[] = ['type' => 'description', 'content' => implode("\n", $paraLines)];
        }

        return $segments;
    }

    private function parseMarkdownTable(array $lines): array
    {
        $rows = [];
        foreach ($lines as $line) {
            // Skip separator rows like |---|---|
            if (preg_match('/^\|[\s\-|:]+\|$/', $line)) continue;
            $cells = array_map('trim', explode('|', trim($line, '|')));
            if (!empty(array_filter($cells, fn($c) => $c !== ''))) {
                $rows[] = $cells;
            }
        }
        return $rows;
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

        // 2. BULK UPDATE
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
                    if (isset($data['graph_labels'])) {
                        $labels = array_map('trim', explode(',', $data['graph_labels'] ?? ''));
                        $values = array_map('trim', explode(',', $data['graph_values'] ?? ''));
                    } else {
                        $lines  = explode("\n", $data['chart_data'] ?? '');
                        $labels = isset($lines[0]) ? array_map('trim', explode(',', $lines[0])) : [];
                        $values = isset($lines[1]) ? array_map('trim', explode(',', $lines[1])) : [];
                    }
                    $content = json_encode([
                        'type'   => $data['chart_type'] ?? 'line',
                        'labels' => array_values(array_filter($labels, fn($v) => $v !== '')),
                        'data'   => array_values(array_filter($values, fn($v) => $v !== '')),
                    ]);
                } elseif ($type === 'list') {
                    $items = array_filter(array_map('trim', explode("\n", $data['list_items'] ?? '')));
                    $content = json_encode([
                        'style' => $data['list_style'] ?? 'bullet',
                        'items' => array_values($items),
                    ]);
                } elseif ($type === 'separator') {
                    $content = json_encode([
                        'type' => $data['separator_type'] ?? 'divider',
                    ]);
                } else {
                    $content = trim($data['content'] ?? '');
                }

                // BUG FIX #13: markdown blocks must not be deleted when empty —
                // they may intentionally be blank while being edited.
                // Also exclude 'table','function','graph','list','separator' (JSON types).
                $jsonTypes = ['photo', 'video', 'exercise', 'markdown', 'table', 'function', 'graph', 'list', 'separator'];
                if ($content === '' && !in_array($type, $jsonTypes)) {
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

    // BUG FIX #12: validation 'in:' had literal newlines — collapsed to single line
    public function store(Request $request, Course $course, Chapter $chapter, Lesson $lesson)
    {
        $validated = $request->validate([
            'type'         => 'required|in:header,description,note,exercise,code,photo,video,math,graph,table,ext,function,list,separator,markdown',
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
                'items' => array_values(array_filter(array_map('trim', explode("\n", $request->input('list_items', ''))))),
            ]);
        } elseif ($request->type === 'separator') {
            $validated['content'] = json_encode([
                'type' => $request->input('separator_type', 'divider'),
            ]);
        } else {
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
