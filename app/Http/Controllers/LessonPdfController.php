<?php

namespace App\Http\Controllers;

use App\Models\block;
use App\Models\lesson;
use Illuminate\Http\Request;
use App\Services\Latex\LatexBuilder;
use App\Services\Latex\LatexCompiler;
use Illuminate\Support\Facades\Response;

class LessonPdfController extends Controller
{
    /*public function download($lessonId)
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
    }*/



    public function showPdf($lessonId)
    {

        $lesson = Lesson::findOrFail($lessonId);

        $blocks = Block::where('lesson_id', $lessonId)
            ->orderBy('block_number','asc')
            ->get();
        $builder = new \App\Services\Latex\LatexBuilder();
        $compiler = new \App\Services\Latex\LatexCompiler();

        // 1. Generate the LaTeX and compile to PDF
        $latex = $builder->build($lesson, $blocks);
        $pdfPath = $compiler->compile($latex);

        if (ob_get_level()) ob_end_clean();
        // 2. Return as 'inline' to open in a new tab
        // Use the file() helper for better stream handling
        return response()->stream(function () use ($pdfPath) {
            readfile($pdfPath);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$lesson->title.'.pdf"',
        ]);
    }
}
