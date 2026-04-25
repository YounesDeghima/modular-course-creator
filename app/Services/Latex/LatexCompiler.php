<?php

namespace App\Services\Latex;

use Symfony\Component\Process\Process;

class LatexCompiler
{
    public function compile(string $latex): string
    {
        $dir = storage_path('app/latex/' . uniqid());
        mkdir($dir, 0777, true);

        file_put_contents("$dir/doc.tex", $latex);

        $process = new Process([
            'xelatex',
            '-interaction=nonstopmode',
            'doc.tex'
        ], $dir);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }

        return "$dir/doc.pdf";
    }
}
