<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

class CodeRunnerController extends Controller
{
    /**
     * POST /admin/code-runner/run
     *
     * Accepts: { language, code, stdin? }
     * Returns: SSE stream of JSON events
     *
     * Each event line:
     *   {"type":"stdout","data":"..."}
     *   {"type":"stderr","data":"..."}
     *   {"type":"exit","code":0}
     *   {"type":"error","message":"..."}
     */
    public function run(Request $request)
    {
        $request->validate([
            'language' => 'required|string|in:python,javascript,c,cpp,bash',
            'code'     => 'required|string|max:200000',
            'stdin'    => 'nullable|string|max:10000',
        ]);

        $language  = $request->input('language');
        $code      = $request->input('code');
        $stdinData = $request->input('stdin', '');

        // Write code + stdin to temp files
        $codeFile  = tempnam(sys_get_temp_dir(), 'ce_code_');
        $stdinFile = tempnam(sys_get_temp_dir(), 'ce_stdin_');

        file_put_contents($codeFile,  $code);
        file_put_contents($stdinFile, $stdinData);

        // Resolve python executable — prefer venv
        $python = $this->resolvePython();

        // Path to code_runner.py (place it in scripts/ or base_path)
        $runner = base_path('scripts/code_runner.py');
        if (! file_exists($runner)) {
            // fallback: same directory as artisan
            $runner = base_path('code_runner.py');
        }

        $cmd = [$python, '-u', $runner, $language, $codeFile, $stdinFile];

        return response()->stream(function () use ($cmd, $codeFile, $stdinFile) {

            // Flush headers immediately
            if (ob_get_level()) {
                ob_end_flush();
            }

            $process = new Process($cmd, null, array_merge(
                $_ENV,
                ['PYTHONUNBUFFERED' => '1', 'PYTHONIOENCODING' => 'utf-8']
            ));

            $process->setTimeout(60);
            $process->start();

            // Stream stdout line by line
            foreach ($process as $type => $data) {
                // $data can have multiple lines — split them
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') continue;

                    // Validate it's JSON the runner emitted
                    $decoded = json_decode($line, true);
                    if ($decoded !== null) {
                        // Forward as SSE data
                        echo "data: " . $line . "\n\n";
                    } else {
                        // Raw output (shouldn't happen, but handle gracefully)
                        $evtType = ($type === Process::OUT) ? 'stdout' : 'stderr';
                        echo "data: " . json_encode(['type' => $evtType, 'data' => $line . "\n"]) . "\n\n";
                    }

                    if (ob_get_level()) ob_flush();
                    flush();
                }

                // Allow client disconnects to stop the process
                if (connection_aborted()) {
                    $process->stop();
                    break;
                }
            }

            $process->wait();

            // Emit final exit if runner didn't
            $exitCode = $process->getExitCode();
            echo "data: " . json_encode(['type' => 'done', 'code' => $exitCode]) . "\n\n";
            if (ob_get_level()) ob_flush();
            flush();

            // Cleanup temp files
            @unlink($codeFile);
            @unlink($stdinFile);

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',   // disable Nginx buffering
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * POST /admin/code-runner/stdin
     *
     * For future WebSocket upgrade or piped stdin injection.
     * Currently stdin is passed with initial run request.
     */
    public function sendStdin(Request $request)
    {
        // Placeholder — stdin is sent with the initial run for SSE mode
        return response()->json(['ok' => true]);
    }

    /**
     * Resolve Python executable.
     * Prefers project venv, falls back to system python.
     */
    private function resolvePython(): string
    {
        $candidates = [
            base_path('scripts/.venv/Scripts/python.exe'),  // Windows venv
            base_path('scripts/.venv/bin/python'),           // Unix venv
            base_path('.venv/Scripts/python.exe'),
            base_path('.venv/bin/python'),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // System python
        foreach (['python3', 'python'] as $name) {
            $found = shell_exec(PHP_OS_FAMILY === 'Windows'
                ? "where $name 2>nul"
                : "which $name 2>/dev/null");
            if ($found) {
                return trim($found);
            }
        }

        return 'python3'; // last resort
    }
}
