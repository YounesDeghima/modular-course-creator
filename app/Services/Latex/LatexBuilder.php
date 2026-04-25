<?php
namespace App\Services\Latex;

class LatexBuilder
{
    public function build($lesson, $blocks): string
    {
        $transformer = new BlockToLatexTransformer();

        $content = collect($blocks)
            ->map(fn($b) => $transformer->transform($b))
            ->filter()         // drop empty strings from unsupported blocks
            ->implode("\n\n");

        return "
\\documentclass{article}
\\usepackage{amsmath}
\\usepackage{graphicx}
\\usepackage{fontspec}
\\usepackage{listings}
\\usepackage{tcolorbox}
\\usepackage{url}
\\usepackage{booktabs}

\\title{{$lesson->title}}
\\author{}
\\date{}

\\begin{document}

\\maketitle

$content

\\end{document}
";
    }
}
