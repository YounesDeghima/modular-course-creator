<?php

namespace App\Http\Controllers;

use App\Models\block;
use App\Models\lesson;
use Illuminate\Http\Request;
use App\Services\Latex\LatexBuilder;
use App\Services\Latex\LatexCompiler;

class LessonPdfController extends Controller
{
    public function download($lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);

        $blocks = Block::where('lesson_id', $lessonId)
            ->orderBy('block_number','asc')
            ->get();

        $builder = new LatexBuilder();
        $latex = $builder->build($lesson, $blocks);

        $compiler = new LatexCompiler();
        $pdfPath = $compiler->compile($latex);

        return response()->download($pdfPath)->deleteFileAfterSend();
    }
}
