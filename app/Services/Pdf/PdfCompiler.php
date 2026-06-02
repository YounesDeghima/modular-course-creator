<?php

namespace App\Services\Pdf;

use League\CommonMark\CommonMarkConverter;
use Spatie\Browsershot\Browsershot;

class PdfCompiler
{
    public function compile(string $markdown): string
    {
        $converter = new CommonMarkConverter();
        $bodyContent = (string) $converter->convert($markdown);

        // Wrap the raw HTML in a styled template with standard print layouts
        $html = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Compiled PDF</title>
            <script src='https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4'></script>

            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css'>
            <script src='https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js'></script>
            <script src='https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js'></script>
            <script src='https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/javascript.min.js'></script>
            <script src='https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/python.min.js'></script>
            <script src='https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/sql.min.js'></script>

            <script>
                window.MathJax = {
                    tex: {
                        inlineMath: [['$', '$'], ['\\\\(', '\\\\)']],
                        displayMath: [['$$', '$$']]
                    }
                };
            </script>
            <script id='MathJax-script' async src='https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js'></script>

            <style>
                @page {
                    size: A4;
                    margin: 20mm;
                }
                body {
                    font-family: system-ui, -apple-system, sans-serif;
                    color: #1e293b;
                }
                /* Prevent text and elements from getting cut in half across page breaks */
                h1, h2, h3, img, tr, pre, blockquote {
                    page-break-inside: avoid;
                }
            </style>
        </head>
        <body class='p-4 max-w-4xl mx-auto'>
            <div class='prose max-w-none
                        prose-headings:font-bold prose-headings:text-slate-900
                        prose-h2:text-2xl prose-h2:border-b prose-h2:pb-2 prose-h2:mt-6 prose-h2:mb-4
                        prose-p:text-slate-700 prose-p:leading-relaxed prose-p:my-3
                        prose-table:w-full prose-table:border-collapse prose-table:my-6
                        prose-th:bg-slate-100 prose-th:border prose-th:border-slate-300 prose-th:p-2 prose-th:text-left prose-th:font-semibold
                        prose-td:border prose-td:border-slate-300 prose-td:p-2
                        prose-blockquote:border-l-4 prose-blockquote:border-blue-500 prose-blockquote:bg-blue-50/50 prose-blockquote:p-4 prose-blockquote:my-4 prose-blockquote:rounded-r-md
                        prose-pre:bg-slate-50 prose-pre:p-4 prose-pre:rounded-md prose-pre:border prose-pre:border-slate-200'>
                {$bodyContent}
            </div>

            <script>
                // Initialize the code highlighting once the window finishes rendering
                window.addEventListener('DOMContentLoaded', () => {
                    hljs.highlightAll();
                });
            </script>
        </body>
        </html>
        ";

        $dir = storage_path('app/pdf/' . uniqid());

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $pdfPath = $dir . '/doc.pdf';

        Browsershot::html($html)
            ->setChromePath('C:\Program Files\Google\Chrome\Application\chrome.exe')
            ->format('A4')
            ->emulateMedia('print') // Forces Chrome to look at your CSS @page margin rules
            ->waitUntilNetworkIdle() // CRITICAL: Gives MathJax and Tailwind time to load via CDN before saving
            ->save($pdfPath);

        return $pdfPath;
    }
}
