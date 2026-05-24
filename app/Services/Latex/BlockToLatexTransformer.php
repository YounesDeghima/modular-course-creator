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
            "\\begin{lstlisting}\n" .
            $this->escape($content) . "\n" . // <--- STOP! Remove $this->escape()
            "\\end{lstlisting}";
    }

    private function renderCode(?string $content): string
    {
        if (!$content) return '';

        // If you have a language property, use it like: [language=php]
        // Otherwise, the default lstset from the preamble will be used.
        return
            "\\begin{lstlisting}\n" .
            $content . "\n" .
            "\\end{lstlisting}";
    }

    private function renderExercise(?string $content): string
    {
        if (!$content) return '';
        return
            "\\begin{tcolorbox}[
            colback=blue!5!white,
            colframe=blue!75!black,
            arc=3pt,              % Rounded corners
            boxrule=0.5pt,        % Thinner border
            leftrule=4pt,         % Thicker left accent bar
            title=Exercise
        ]\n" .
            $this->escape($content) . "\n" .
            "\\end{tcolorbox}";
    }

    private function renderPhoto(?string $content): string
    {
        if (!$content) return '';
        // $content is expected to be a file path or URL
        return
            "\\begin{figure}[htbp]\n" .
            "\\centering\n" .
            "\\includegraphics[width=0.8\\linewidth]{" . $this->escapePath($content) . "}\n" .
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



    private function sanitizeColor(string $color): string
    {
        $color = trim($color);

        // remove quotes if JSON sends them
        $color = trim($color, "\"'");

        // whitelist allowed colors (important)
        $allowed = ['blue', 'red', 'green', 'black', 'orange', 'purple'];

        return in_array($color, $allowed) ? $color : 'blue';
    }

    private function renderFunction(?string $content): string
    {
        if (!$content) return '';
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['function'])) {
            return "\\[ " . $content . " \\]"; // Raw math if JSON fails
        }

        $func = $data['function'];
        // Do NOT use $this->escape() on the function string used inside the plot
        $plotFunc = str_replace(['y=', ' '], '', $func);
        $plotFunc = preg_replace('/(\d)([a-zA-Z])/', '$1*$2', $plotFunc);

        return "
\\begin{center}
\\begin{tikzpicture}
    \\begin{axis}[
        axis lines = middle,
        xlabel = {\(x\)},
        ylabel = {\(y\)},
        xmin=" . ($data['xmin'] ?? -5) . ", xmax=" . ($data['xmax'] ?? 5) . ",
        ymin=" . ($data['ymin'] ?? -5) . ", ymax=" . ($data['ymax'] ?? 5) . ",
        grid = both,
        unbounded coords=discard % Critical: ignores math errors like 1/0
    ]
    \\addplot [
        domain=" . ($data['xmin'] ?? -5) . ":" . ($data['xmax'] ?? 5) . ",
        samples=100,
        color=" . $this->sanitizeColor($data['color'] ?? 'blue') . ",
        thick
    ] {" . $plotFunc . "};
    \\end{axis}
\\end{tikzpicture}
\\end{center}";
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
