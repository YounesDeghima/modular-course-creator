<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class CodeEditorController extends Controller
{
    /**
     * Piston base URL — running locally via Podman/WSL2
     */
    private string $pistonBase = 'http://localhost:2000/api/v2';

    // ──────────────────────────────────────────────
    //  Standalone editor pages
    // ──────────────────────────────────────────────

    /** Admin standalone editor */
    public function adminEditor()
    { $user = Auth::user();
        return view('pages.admin.code-editor', [
            'name'  => $user?->name ?? 'Guest',
            'email' => $user?->email ?? '',
            'id'    => $user?->id ?? null,
        ]);
    }

    /** Student standalone editor */
    public function userEditor()
    {
        return view('pages.user.code-editor', [
            'name'  => $user?->name ?? 'Guest',
            'email' => $user?->email ?? '',
            'id'    => $user?->id ?? null,
        ]);
    }

    // ──────────────────────────────────────────────
    //  API: list available runtimes from Piston
    // ──────────────────────────────────────────────

    public function runtimes()
    {
        try {
            $response = Http::timeout(5)->get("{$this->pistonBase}/runtimes");
            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Piston unreachable: ' . $e->getMessage()], 503);
        }
    }

    // ──────────────────────────────────────────────
    //  API: execute code
    // ──────────────────────────────────────────────

    public function execute(Request $request)
    {
        $request->validate([
            'language' => 'required|string|max:50',
            'version'  => 'required|string|max:20',
            'code'     => 'required|string|max:65536',  // 64 KB hard cap
            'stdin'    => 'nullable|string|max:4096',
        ]);

        // ── Rate limit: 30 executions / minute per user ──
        $key    = 'code-run:' . ($request->user()?->id ?? $request->ip());
        $maxRuns = 30;

        if (RateLimiter::tooManyAttempts($key, $maxRuns)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => "Too many runs. Try again in {$seconds}s."
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // ── Forward to Piston ──
        try {
            $payload = [
                'language' => $request->input('language'),
                'version'  => $request->input('version'),
                'files'    => [
                    [
                        'name'    => 'main',
                        'content' => $request->input('code'),
                    ]
                ],
                'stdin'         => $request->input('stdin', ''),
                'args'          => [],
                'compile_timeout' => 3000,
                'run_timeout'     => 3000,
                'compile_memory_limit' => -1,
                'run_memory_limit'     => 256000000, // 256 MB
            ];

            $response = Http::timeout(20)->post("{$this->pistonBase}/execute", $payload);

            if (!$response->ok()) {
                return response()->json([
                    'error' => 'Piston error: ' . $response->body()
                ], 500);
            }

            $data = $response->json();

            // Sanitise output — escape HTML to prevent XSS
            $stdout = htmlspecialchars($data['run']['stdout'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $stderr = htmlspecialchars($data['run']['stderr'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $output = htmlspecialchars($data['run']['output'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return response()->json([
                'stdout'    => $stdout,
                'stderr'    => $stderr,
                'output'    => $output,
                'exit_code' => $data['run']['code'] ?? null,
                'signal'    => $data['run']['signal'] ?? null,
                'compile'   => [
                    'stdout' => htmlspecialchars($data['compile']['stdout'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'stderr' => htmlspecialchars($data['compile']['stderr'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'code'   => $data['compile']['code'] ?? null,
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Execution failed: ' . $e->getMessage()
            ], 503);
        }
    }

    // ──────────────────────────────────────────────
    //  API: run test cases (judge mode)
    //  Runs each test case individually, returns pass/fail per case
    // ──────────────────────────────────────────────

    public function judge(Request $request)
    {
        $request->validate([
            'language'   => 'required|string|max:50',
            'version'    => 'required|string|max:20',
            'code'       => 'required|string|max:65536',
            'test_cases' => 'required|array|max:20',
            'test_cases.*.input'           => 'nullable|string|max:4096',
            'test_cases.*.expected_output' => 'required|string|max:4096',
        ]);

        // Rate limit: 10 judge submissions / minute per user
        $key = 'code-judge:' . ($request->user()?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json(['error' => "Too many submissions. Try again in {$seconds}s."], 429);
        }
        RateLimiter::hit($key, 60);

        $results = [];
        $allPassed = true;

        foreach ($request->input('test_cases') as $i => $tc) {
            try {
                $payload = [
                    'language' => $request->input('language'),
                    'version'  => $request->input('version'),
                    'files'    => [['name' => 'main', 'content' => $request->input('code')]],
                    'stdin'    => $tc['input'] ?? '',
                    'run_timeout' => 3000,
                    'run_memory_limit' => 256000000,
                ];

                $response = Http::timeout(15)->post("{$this->pistonBase}/execute", $payload);
                $data = $response->json();

                $actualRaw  = trim($data['run']['output'] ?? '');
                $expectedRaw = trim($tc['expected_output']);

                $passed = $actualRaw === $expectedRaw;
                if (!$passed) $allPassed = false;

                $results[] = [
                    'index'    => $i,
                    'passed'   => $passed,
                    'input'    => $tc['input'] ?? '',
                    'expected' => htmlspecialchars($expectedRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'actual'   => htmlspecialchars($actualRaw,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'stderr'   => htmlspecialchars($data['run']['stderr'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'exit_code'=> $data['run']['code'] ?? null,
                ];

            } catch (\Throwable $e) {
                $allPassed = false;
                $results[] = [
                    'index'    => $i,
                    'passed'   => false,
                    'error'    => 'Execution failed: ' . $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'results'    => $results,
            'all_passed' => $allPassed,
            'passed'     => collect($results)->where('passed', true)->count(),
            'total'      => count($results),
        ]);
    }
}
