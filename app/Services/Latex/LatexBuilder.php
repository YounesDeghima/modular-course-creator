<?php
namespace App\Services\Latex;

class LatexBuilder
{
    // LatexBuilder.php

    public function build($lesson, $blocks): string
    {
        $transformer = new BlockToLatexTransformer();

        $content = collect($blocks)
            ->map(fn($b) => $transformer->transform($b))
            ->filter()
            ->implode("\n\n");

        return "
\\documentclass{article}

% --- Packages ---
\\usepackage[utf8]{inputenc}
\\usepackage{amsmath}
\\usepackage{graphicx}
\\usepackage{listings}
\\usepackage{tikz}
\\usepackage{xcolor}
\\usepackage{tcolorbox}
\\usepackage{url}
\\usepackage{booktabs}

% --- Code Block Styling ---
\\definecolor{codegreen}{rgb}{0,0.6,0}
\\definecolor{codegray}{rgb}{0.5,0.5,0.5}
\\definecolor{codepurple}{rgb}{0.58,0,0.82}
\\definecolor{backcolour}{rgb}{0.95,0.95,0.92}

\\lstset{
    backgroundcolor=\color{backcolour},
    commentstyle=\color{codegreen},
    keywordstyle=\color{blue},
    numberstyle=\tiny\color{codegray},
    stringstyle=\color{orange},
    basicstyle=\ttfamily\footnotesize,
    breaklines=true,
    frame=none,               % Remove the ugly border lines
    xleftmargin=10pt,         % Indent the code block
    showstringspaces=false,
    captionpos=b
}

\\usepackage{pgfplots}
\\pgfplotsset{compat=1.18}

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
