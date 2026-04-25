<?php
namespace App\Services\Latex;

class LatexBuilder
{
    public function build($lesson, $blocks): string
    {
        $transformer = new BlockToLatexTransformer();

        $content = collect($blocks)
            ->map(fn($b) => $transformer->transform($b))
            ->implode("\n\n");

        return "
\\documentclass{article}
\\usepackage{amsmath}
\\usepackage{graphicx}
\\usepackage[utf8]{inputenc}

\\title{{$lesson->title}}

\\begin{document}

\\maketitle

$content

\\end{document}
";
    }
}
