<?php
namespace App\Services\Latex;
namespace App\Services\Pdf;

use App\Services\Pdf\BlockToMarkdownTransformer;

class MarkdownBuilder
{
    public function build($lesson, $blocks): string
    {
        $transformer = new BlockToMarkdownTransformer();

        return collect($blocks)
            ->map(fn($b) => $transformer->transform($b))
            ->filter()
            ->implode("\n\n");
    }
}
