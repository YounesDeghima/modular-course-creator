<?php

namespace App\Services\Latex;

class BlockToLatexTransformer
{
    public function transform($block): string
    {
        return match ($block->type) {
            'header'      => $this->renderHeader($block->content),
            'description' => $this->renderDescription($block->content),
            'note'        => $this->renderNote($block->content),
            'code'        => $this->renderCode($block->content),
            'exercise'    => $this->renderExercise($block->content),
            'photo'       => $this->renderPhoto($block->content),
            'video'       => $this->renderVideo($block->content),
            'math'        => $this->renderMath($block->content),
            'graph'       => $this->renderGraph($block->content),
            'table'       => $this->renderTable($block->content),
            'ext'         => $this->renderExt($block->content),
            'function'    => $this->renderFunction($block->content),
            default       => '',
        };
    }

    // -------------------------------------------------------------------------
    // Block renderers
    // -------------------------------------------------------------------------

    private function renderHeader(string $content): string
    {
        return "\\section{" . $this->escape($content) . "}";
    }

    private function renderDescription(string $content): string
    {
        return $this->escape($content);
    }

    private function renderNote(?string $content): string
    {
        if (!$content) return '';
        return
            "\\begin{tcolorbox}[colback=yellow!10!white, colframe=yellow!60!black, title=Note]\n" .
            $this->escape($content) . "\n" .
            "\\end{tcolorbox}";
    }

    private function renderCode(?string $content): string
    {
        if (!$content) return '';
        // lstlisting preserves verbatim text — no escaping needed
        return
            "\\begin{lstlisting}\n" .
            $content . "\n" .
            "\\end{lstlisting}";
    }

    private function renderExercise(?string $content): string
    {
        if (!$content) return '';
        return
            "\\begin{tcolorbox}[colback=blue!5!white, colframe=blue!50!black, title=Exercise]\n" .
            $this->escape($content) . "\n" .
            "\\end{tcolorbox}";
    }

    private function renderPhoto(?string $content): string
    {
        if (!$content) return '';
        // $content is expected to be a file path or URL
        return
            "\\begin{figure}[h]\n" .
            "\\centering\n" .
            "\\includegraphics[width=0.9\\linewidth]{" . $this->escapePath($content) . "}\n" .
            "\\end{figure}";
    }

    private function renderVideo(?string $content): string
    {
        if (!$content) return '';
        // Videos can't embed in PDF — render as a labelled hyperlink
        return
            "\\begin{tcolorbox}[colback=gray!10!white, colframe=gray!50!black, title=Video]\n" .
            "\\url{" . $content . "}\n" .
            "\\end{tcolorbox}";
    }

    private function renderMath(?string $content): string
    {
        if (!$content) return '';
        return "\\[\n" . $content . "\n\\]";
    }

    private function renderGraph(?string $content): string
    {
        if (!$content) return '';
        // If graph block stores an image path, treat like photo.
        // If it stores a description, render as a captioned note.
        if ($this->looksLikePath($content)) {
            return
                "\\begin{figure}[h]\n" .
                "\\centering\n" .
                "\\includegraphics[width=0.9\\linewidth]{" . $this->escapePath($content) . "}\n" .
                "\\end{figure}";
        }
        return
            "\\begin{tcolorbox}[colback=green!5!white, colframe=green!40!black, title=Graph]\n" .
            $this->escape($content) . "\n" .
            "\\end{tcolorbox}";
    }

    private function renderTable(?string $content): string
    {
        if (!$content) return '';

        // Expects content as pipe-separated CSV, one row per line:
        //   Col A | Col B | Col C
        //   val 1 | val 2 | val 3
        $rows = array_filter(array_map('trim', explode("\n", $content)));
        if (empty($rows)) return '';

        $parsed = array_map(fn($r) => array_map('trim', explode('|', $r)), $rows);
        $cols   = max(array_map('count', $parsed));
        $spec   = implode(' | ', array_fill(0, $cols, 'l'));

        $latex = "\\begin{tabular}{" . $spec . "}\n\\hline\n";
        foreach ($parsed as $i => $cells) {
            // Pad short rows
            while (count($cells) < $cols) $cells[] = '';
            $escaped = array_map(fn($c) => $this->escape($c), $cells);
            $latex  .= implode(' & ', $escaped) . " \\\\\n";
            if ($i === 0) $latex .= "\\hline\n"; // header separator
        }
        $latex .= "\\hline\n\\end{tabular}";
        return $latex;
    }

    private function renderExt(?string $content): string
    {
        if (!$content) return '';
        // External resource — render as labelled hyperlink
        return
            "\\begin{tcolorbox}[colback=gray!5!white, colframe=gray!40!black, title=External Resource]\n" .
            "\\url{" . $content . "}\n" .
            "\\end{tcolorbox}";
    }

    private function renderFunction(?string $content): string
    {
        if (!$content) return '';
        // A named function definition — display as math or code depending on content
        if (str_contains($content, '=') || str_contains($content, '\\')) {
            return "\\[\n" . $content . "\n\\]";
        }
        return
            "\\begin{lstlisting}\n" .
            $content . "\n" .
            "\\end{lstlisting}";
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function escape(string $text): string
    {
        // strtr does all replacements simultaneously — no double-escaping
        return strtr($text, [
            '\\'  => '\\textbackslash{}',
            '{'   => '\\{',
            '}'   => '\\}',
            '$'   => '\\$',
            '&'   => '\\&',
            '#'   => '\\#',
            '_'   => '\\_',
            '%'   => '\\%',
            '^'   => '\\^{}',
            '~'   => '\\textasciitilde{}',
        ]);
    }

    private function escapePath(string $path): string
    {
        // Paths only need { } escaped in LaTeX
        return strtr($path, ['{' => '\\{', '}' => '\\}']);
    }

    private function looksLikePath(string $content): bool
    {
        return (bool) preg_match('/\.(png|jpg|jpeg|gif|pdf|svg)$/i', trim($content));
    }
}
