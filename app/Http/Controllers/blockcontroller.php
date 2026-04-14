<?php

namespace App\Http\Controllers;

use App\Models\block;
use App\Models\chapter;
use App\Models\course;
use App\Models\lesson;
use App\Models\exercisesolution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\lesson_progress;


class blockcontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Course $course, Chapter $chapter, Lesson $lesson)
    {
        // 1. Get all the data first
        $blocks = $lesson->blocks()
            ->with('solutions')
            ->orderBy('block_number', 'asc')
            ->get();

        $block_count = $blocks->count();
        $chapters = $course->chapters()->with('lessons')->get();
        $chapter_count = $chapters->count();

        // 2. Get the user data for the layout (Crucial!)
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        // 3. The AJAX Check

            if (request()->ajax()) {
                return view('pages.admin.chapters', compact(
                    'blocks',
                    'course',
                    'chapter',
                    'lesson',
                    'block_count',
                    'chapters',       // <--- Add this
                    'chapter_count', // <--- Add this
                    'id',
                    'name',
                    'email'
                ))->fragment('main-content');
            }




        return view('pages.admin.chapters', compact(
            'blocks', 'block_count', 'course', 'chapter', 'lesson',
            'chapters', 'chapter_count', 'admin', 'id', 'name', 'email'
        ));
    }



    public function updateAll(Request $request, $courseId, $chapterId, $lessonId)
    {
        // 1. HANDLE REORDERING
        if ($request->has('move')) {
            $parts = explode(':', $request->move);
            $currentBlockId = $parts[0];
            $direction = $parts[1];

            $currentBlock = \App\Models\block::findOrFail($currentBlockId);
            $currentOrder = $currentBlock->block_number;

            if ($direction === 'up') {
                $previousBlock = \App\Models\block::where('lesson_id', $lessonId)
                    ->where('block_number', '<', $currentOrder)
                    ->orderBy('block_number', 'desc')
                    ->first();

                if ($previousBlock) {
                    $currentBlock->update(['block_number' => $previousBlock->block_number]);
                    $previousBlock->update(['block_number' => $currentOrder]);
                }
            } else {
                $nextBlock = \App\Models\block::where('lesson_id', $lessonId)
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
                $block = \App\Models\block::find($id);
                if (!$block) continue;

                $type = $data['type'] ?? $block->type;
                $content = '';

                // Handle file-based blocks
                if (in_array($type, ['photo', 'video'])) {
                    if (isset($data['content_file']) && $data['content_file'] instanceof \Illuminate\Http\UploadedFile) {
                        if ($block->content && \Storage::exists('public/' . $block->content)) {
                            \Storage::delete('public/' . $block->content);
                        }
                        $content = $data['content_file']->store('blocks', 'public');
                    } else {
                        $content = $data['content'] ?? $block->content;
                    }
                }
                // Handle table JSON
                elseif ($type === 'table') {
                    $content = json_encode($data['table_data'] ?? json_decode($block->content, true) ?? []);
                }
                // Handle function JSON
                elseif ($type === 'function') {
                    $content = json_encode([
                        'function' => $data['func_expression'] ?? 'sin(x)',
                        'x_min' => floatval($data['x_min'] ?? -10),
                        'x_max' => floatval($data['x_max'] ?? 10),
                        'y_min' => floatval($data['y_min'] ?? -5),
                        'y_max' => floatval($data['y_max'] ?? 5),
                        'color' => $data['color'] ?? '#4f46e5',
                        'step' => floatval($data['step'] ?? 0.1)
                    ]);
                }
                // Handle graph JSON
                elseif ($type === 'graph') {
                    $lines = explode("\n", $data['chart_data'] ?? '');
                    $labels = isset($lines[0]) ? array_map('trim', explode(',', $lines[0])) : [];
                    $values = isset($lines[1]) ? array_map('trim', explode(',', $lines[1])) : [];
                    $content = json_encode([
                        'type' => $data['chart_type'] ?? 'line',
                        'labels' => $labels,
                        'data' => $values
                    ]);
                }
                // Standard text content
                else {
                    $content = trim($data['content'] ?? '');
                }

                // Delete empty blocks (except file/exercise types)
                if ($content === '' && !in_array($type, ['photo', 'video', 'exercise'])) {
                    $block->delete();
                    continue;
                }

                $block->update([
                    'content' => $content,
                    'type'    => $type,
                    'block_number'  => $data['block_number'] ?? $block->block_number,
                ]);

                // Handle exercise solutions
                if ($type === 'exercise') {
                    if ($block->solutions()->count() === 0) {
                        $block->solutions()->create([
                            'solution_number' => 1,
                            'content' => 'nothing here yet',
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
                                                'content' => $newContent,
                                                'solution_number' => $block->solutions()->count() + 1
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

        return back()->with('success', 'Lesson layout updated successfully!');
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Course $course, Chapter $chapter, Lesson $lesson)
    {
        $validated = $request->validate([
            'type' => 'required|in:header,description,note,exercise,code,photo,video,math,graph,table,ext,function',
            'block_number' => 'required|integer',
        ]);

        $validated['lesson_id'] = $lesson->id;

        // Handle file uploads for photo/video
        if (in_array($request->type, ['photo', 'video'])) {
            if ($request->hasFile('content_file')) {
                $validated['content'] = $request->file('content_file')->store('blocks', 'public');
            } else {
                $validated['content'] = '';
            }
        }
        // Handle structured data
        elseif ($request->type === 'table') {
            $validated['content'] = json_encode($request->input('table_data', [['Column 1', 'Column 2'], ['Row 1', 'Data']]));
        }elseif ($request->type === 'function') {
            $validated['content'] = json_encode([
                'function' => $request->input('func_expression', 'sin(x)'),
                'x_min' => floatval($request->input('x_min', -10)),
                'x_max' => floatval($request->input('x_max', 10)),
                'y_min' => floatval($request->input('y_min', -5)),
                'y_max' => floatval($request->input('y_max', 5)),
                'color' => $request->input('func_color', '#4f46e5'),
                'step' => 0.1
            ]);
        }
        elseif ($request->type === 'graph') {
            $lines = explode("\n", $request->input('chart_data', "Jan,Feb,Mar\n10,20,15"));
            $labels = isset($lines[0]) ? array_map('trim', explode(',', $lines[0])) : [];
            $values = isset($lines[1]) ? array_map('trim', explode(',', $lines[1])) : [];
            $validated['content'] = json_encode([
                'type' => $request->input('chart_type', 'line'),
                'labels' => $labels,
                'data' => $values
            ]);
        }
        else {
            $validated['content'] = $request->input('content', '');
        }

        $block = Block::create($validated);

        if ($block->type == 'exercise') {
            $block->solutions()->create([
                'solution_number' => 1,
                'block_id' => $block->id,
                'content' => 'nothing here yet',
            ]);
        }

        return response()->json(['block' => $block], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, course $course, chapter $chapter,lesson $lesson,block $block)
    {


        $direction = $request->input('update');

        if($direction === 'up'){
            $previous = block::where('lesson_id',$lesson->id)
                ->where('block_number','<',$block->block_number)
                ->orderBy('block_number','desc')
                ->first();

            if($previous){
                $temp = $block->block_number;
                $block->block_number=$previous->block_number;
                $previous->block_number = $temp;

                $block->save();
                $previous->save();

            }


            return back();

        }
        if($direction === 'down'){
            $previous = block::where('lesson_id',$lesson->id)
                ->where('block_number','>',$block->block_number)
                ->orderBy('block_number','asc')
                ->first();

            if($previous){
                $temp = $block->block_number;
                $block->block_number=$previous->block_number;
                $previous->block_number = $temp;

                $block->save();
                $previous->save();

            }


            return back();

        }



        $validated = $request->validate([
            'type' => 'required|in:header,description,note,exercise,code,photo,video,math,graph,table,ext,function',
            'block_number'=>'required|integer',
            'content' => 'required|string',
        ]);


        if($block->type=='exercise'){
            foreach ($block->solutions as $solution) {
                $solution->content = $request->input('solution');

                $solution->save();

            }
        }

        $block->update($validated);

        return redirect()->back()->with('success', 'block updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(course $course ,chapter $chapter,lesson $lesson,block $block)
    {
        $block = block::findOrFail($block->id);
        $block->delete();

        return redirect()->back()->with('success', 'block deleted');
    }
}
