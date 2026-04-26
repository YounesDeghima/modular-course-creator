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
            'C:\\Users\\merie\\AppData\\Local\\Programs\\MiKTeX\\miktex\\bin\\x64\\xelatex.exe',
            '-interaction=nonstopmode',
            'doc.tex'
        ], $dir, [
            'HOME'        => 'C:\\Users\\merie',
            'USERPROFILE' => 'C:\\Users\\merie',
            'APPDATA'     => 'C:\\Users\\merie\\AppData\\Roaming',
            'LOCALAPPDATA'=> 'C:\\Users\\merie\\AppData\\Local',
        ]);

        $process->setTimeout(60);
        $process->run();

        $pdfPath = "$dir/doc.pdf";

        if (!file_exists($pdfPath)) {
            $log = file_exists("$dir/doc.log") ? file_get_contents("$dir/doc.log") : $process->getOutput();
            throw new \Exception($log);
        }

        return $pdfPath;
    }
}
