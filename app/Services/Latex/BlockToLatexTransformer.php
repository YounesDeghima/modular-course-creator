<?php

namespace App\Services\Latex;

class BlockToLatexTransformer
{
    public function transform($block): string
    {
        return match ($block->type) {
            'heading' => "\\section{{$block->content}}",
            'text'    => $this->escape($block->content),
            'math'    => "\\[{$block->content}\\]",
            'image'   => "\\includegraphics[width=\\linewidth]{{$block->content}}",
            default   => '',
        };
    }

    private function escape(string $text): string
    {
        return str_replace(
            ['\\', '{', '}', '$', '&', '#', '_', '%'],
            ['\\textbackslash{}', '\\{', '\\}', '\\$', '\\&', '\\#', '\\_', '\\%'],
            $text
        );
    }
}
