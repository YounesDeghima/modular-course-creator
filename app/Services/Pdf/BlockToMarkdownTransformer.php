<?php

namespace App\Services\Pdf;

class BlockToMarkdownTransformer
{
    public function transform($block): string
    {
        return match ($block->type) {
            'header'      => $this->renderHeader($block->content),
            'description' => $this->renderDescription($block->content),
            'note'        => $this->renderNote($block->content),
            'code'        => $this->renderCode($block->content, $block->language ?? $block->lang ?? null),
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
        return "## " . $content;
    }

    private function renderDescription(string $content): string
    {
        return $content;
    }

    private function renderNote(?string $content): string
    {
        if (!$content) return '';

        // Changed from raw code backticks to a blockquote alert container.
        // This allows nested markdown elements like lists, bold text, or math equations
        // to render beautifully instead of printing as unformatted plain text.
        return
            "> **Note:**\n" .
            ">\n" .
            $this->indentAsBlockquote($content);
    }

    private function renderCode(?string $content, ?string $language = null): string
    {
        if (!$content) return '';

        // Safely fallback to plaintext if no language context is attached to the block
        $lang = $language ?? 'plaintext';
        return
            "```" . $lang . "\n" .
            $content . "\n" .
            "```";
    }

    private function renderExercise(?string $content): string
    {
        if (!$content) return '';
        return
            "> **Exercise**\n" .
            ">\n" .
            $this->indentAsBlockquote($content);
    }

    private function renderPhoto(?string $content): string
    {
        if (!$content) return '';
        return "![Image](" . trim($content) . ")";
    }

    private function renderVideo(?string $content): string
    {
        if (!$content) return '';
        return
            "> **Video**\n" .
            ">\n" .
            "> [Watch video](" . trim($content) . ")";
    }

    private function renderMath(?string $content): string
    {
        if (!$content) return '';

        $cleanContent = trim($content);

        // Iteratively peel away up to two layers of external delimiters ($$, \$\$, $, \$)
        // without disturbing inner LaTeX layout slashes like \frac or \begin
        for ($i = 0; $i < 2; $i++) {
            $cleanContent = preg_replace('/^(\\\*\$\$?)\s*/', '', $cleanContent);
            $cleanContent = preg_replace('/\s*(\\\*\$\$?)$/', '', $cleanContent);
            $cleanContent = trim($cleanContent);
        }

        // Output matching standardized layout rules for MathJax
        return "$$\n" . $cleanContent . "\n$$";
    }

    private function renderGraph(?string $content): string
    {
        if (!$content) return '';
        if ($this->looksLikePath($content)) {
            return "![Graph](" . trim($content) . ")";
        }
        return
            "> **Graph**\n" .
            ">\n" .
            $this->indentAsBlockquote($content);
    }

    private function renderTable(?string $content): string
    {
        if (!$content) return '';

        $parsed = null;

        // NEW: Look for an embedded JSON array matrix [[...]] inside the text block
        // to filter out decorative markdown pipes that contaminate the content
        if (preg_match('/\[\s*\[.*\]\s*\]/s', $content, $matches)) {
            $data = json_decode($matches[0], true);
            if (is_array($data) && !empty($data)) {
                $parsed = $data;
            }
        }

        // Fallback parser if the block contains standard pipe-separated values
        if (!$parsed) {
            $normalized = str_replace('||', "\n", $content);
            $rows = array_filter(array_map('trim', explode("\n", $normalized)));

            if (empty($rows)) return '';

            $parsed = [];
            foreach ($rows as $row) {
                $trimmedRow = trim($row, '| ');
                if (empty($trimmedRow) || str_contains($trimmedRow, '---')) {
                    continue;
                }
                $parsed[] = array_map('trim', explode('|', $trimmedRow));
            }
        }

        if (empty($parsed)) return '';

        $cols = max(array_map('count', $parsed));
        $markdown = '';

        foreach ($parsed as $i => $cells) {
            while (count($cells) < $cols) $cells[] = '';
            $markdown .= '| ' . implode(' | ', $cells) . " |\n";

            if ($i === 0) {
                $markdown .= '| ' . implode(' | ', array_fill(0, $cols, '---')) . " |\n";
            }
        }

        return $markdown;
    }

    private function renderExt(?string $content): string
    {
        if (!$content) return '';
        return
            "> **External Resource**\n" .
            ">\n" .
            "> [Link](" . trim($content) . ")";
    }

    private function renderFunction(?string $content): string
    {
        if (!$content) return '';
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['function'])) {
            return "`" . $content . "`";
        }

        $func = $data['function'];
        $xmin = $data['xmin'] ?? -5;
        $xmax = $data['xmax'] ?? 5;
        $ymin = $data['ymin'] ?? -5;
        $ymax = $data['ymax'] ?? 5;
        $color = $this->sanitizeColor($data['color'] ?? 'blue');

        return
            "**Function Plot**\n\n" .
            "Function: `" . $this->escapeMarkdown($func) . "`\n\n" .
            "| Parameter | Value |\n" .
            "| --- | --- |\n" .
            "| X Min | " . $xmin . " |\n" .
            "| X Max | " . $xmax . " |\n" .
            "| Y Min | " . $ymin . " |\n" .
            "| Y Max | " . $ymax . " |\n" .
            "| Color | " . $color . " |\n";
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function escapeMarkdown(string $text): string
    {
        return strtr($text, [
            '\\' => '\\\\',
            '`'  => '\\`',
            '*'  => '\\*',
            '_'  => '\\_',
            '['  => '\\[',
            ']'  => '\\]',
            '('  => '\\(',
            ')'  => '\\)',
            '#'  => '\\#',
            '+'  => '\\+',
            '-'  => '\\-',
            '.'  => '\\.',
            '!'  => '\\!',
        ]);
    }

    private function sanitizeColor(string $color): string
    {
        $color = trim($color);
        $color = trim($color, "\"'");
        return $color;
    }

    private function indentAsBlockquote(string $text): string
    {
        $lines = explode("\n", $text);
        return implode("\n", array_map(fn($line) => "> " . $line, $lines));
    }

    private function looksLikePath(string $content): bool
    {
        return (bool) preg_match('/\.(png|jpg|jpeg|gif|pdf|svg|webp)$/i', trim($content));
    }
}
