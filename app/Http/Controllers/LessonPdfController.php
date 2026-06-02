<?php

namespace App\Http\Controllers;

use App\Models\block;
use App\Models\lesson;
use App\Services\Pdf\PdfCompiler;
use Illuminate\Http\Request;
use App\Services\Pdf\MarkdownBuilder;

use Illuminate\Support\Facades\Response;

class LessonPdfController extends Controller
{
    /*public function download($lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);

        $blocks = Block::where('lesson_id', $lessonId)
            ->orderBy('block_number','asc')
            ->get();

        $builder = new MarkdownBuilder();
        $latex = $builder->build($lesson, $blocks);

        $compiler = new PdfCompiler();
        $pdfPath = $compiler->compile($latex);

        return response()->download($pdfPath)->deleteFileAfterSend();
    }*/



    public function showPdf($lessonId)
    {

        $lesson = Lesson::findOrFail($lessonId);

        $blocks = Block::where('lesson_id', $lessonId)
            ->orderBy('block_number')
            ->get();

        $builder = new MarkdownBuilder();
        $compiler = new PdfCompiler();

        $markdown = $builder->build($lesson, $blocks);
        $pdfPath = $compiler->compile($markdown);

        return response()->stream(function () use ($pdfPath) {
            readfile($pdfPath);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$lesson->title.'.pdf"',
        ]);
    }
}
