<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CodeRunnerController extends Controller
{
    /**
     * Piston API base URL.
     * On Windows dev: Docker Desktop runs Piston at localhost:2000
     * On Linux server: same, docker maps it to localhost:2000
     */
    private string $pistonUrl;

    public function __construct()
    {
        $this->pistonUrl = config('services.piston.url', 'http://localhost:2000');
    }

    /**
     * Returns all runtimes available in the local Piston instance.
     * Called once on page load to populate language dropdowns.
     */
    public function runtimes()
    {
        try {
            $response = Http::timeout(5)->get("{$this->pistonUrl}/api/v2/runtimes");

            if ($response->failed()) {
                return response()->json(['error' => 'Piston not reachable'], 503);
            }

            // Return simplified list: language + version
            $runtimes = collect($response->json())
                ->map(fn($r) => [
                    'language' => $r['language'],
                    'version'  => $r['version'],
                    'aliases'  => $r['aliases'] ?? [],
                ])
                ->sortBy('language')
                ->values();

            return response()->json($runtimes);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Piston not running. Start it with: docker run -d -p 2000:2000 ghcr.io/engineer-man/piston'], 503);
        }
    }

    /**
     * Execute code via Piston.
     * Used by both the code block in lessons AND the codeeditor page.
     *
     * POST /code/run
     * Body: { language, version, code, stdin? }
     */
    public function run(Request $request)
    {
        $request->validate([
            'language' => 'required|string',
            'version'  => 'required|string',
            'code'     => 'required|string|max:50000',
            'stdin'    => 'nullable|string|max:10000',
        ]);

        try {
            $response = Http::timeout(30)->post("{$this->pistonUrl}/api/v2/execute", [
                'language' => $request->language,
                'version'  => $request->version,
                'files'    => [
                    [
                        'name'    => 'main' . $this->extensionFor($request->language),
                        'content' => $request->code,
                    ]
                ],
                'stdin'    => $request->input('stdin', ''),
                'args'     => [],
                'run_timeout' => 10000,   // 10 seconds max
                'compile_timeout' => 15000,
                'compile_memory_limit' => -1,
                'run_memory_limit'     => 128000000, // 128 MB
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Execution failed: ' . $response->body()
                ], 500);
            }

            $result = $response->json();

            return response()->json([
                'stdout'   => $result['run']['stdout']   ?? '',
                'stderr'   => $result['run']['stderr']   ?? '',
                'code'     => $result['run']['code']     ?? 0,
                'signal'   => $result['run']['signal']   ?? null,
                'compile'  => [
                    'stdout' => $result['compile']['stdout'] ?? '',
                    'stderr' => $result['compile']['stderr'] ?? '',
                    'code'   => $result['compile']['code']   ?? 0,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Save a codeeditor session entry for the authenticated user.
     * POST /codeeditor/save
     */
    public function saveSession(Request $request)
    {
        $request->validate([
            'language' => 'required|string',
            'version'  => 'required|string',
            'code'     => 'required|string|max:50000',
            'title'    => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();

        DB::table('code_sessions')->insert([
            'user_id'    => $userId,
            'language'   => $request->language,
            'version'    => $request->version,
            'code'       => $request->code,
            'title'      => $request->input('title', 'Untitled'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Keep only last 50 sessions per user to avoid bloat
        $ids = DB::table('code_sessions')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->pluck('id')
            ->skip(50);

        if ($ids->count()) {
            DB::table('code_sessions')->whereIn('id', $ids)->delete();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Load session history for the authenticated user.
     * GET /codeeditor/history
     */
    public function history()
    {
        $sessions = DB::table('code_sessions')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'title', 'language', 'version', 'code', 'created_at']);

        return response()->json($sessions);
    }

    /**
     * Delete a session.
     * DELETE /codeeditor/history/{id}
     */
    public function deleteSession($id)
    {
        DB::table('code_sessions')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Load the codeeditor page view.
     */
    public function editorPage()
    {
        $user  = Auth::user();
        $id    = $user->id;
        $name  = $user->name;
        $email = $user->email;

        return view('pages.codeeditor', compact('id', 'name', 'email'));
    }

    /**
     * Maps language names to file extensions so Piston names the file correctly.
     * Piston uses file extension to determine compiler in some runtimes.
     */
    private function extensionFor(string $language): string
    {
        return match (strtolower($language)) {
            'python', 'python3', 'python2' => '.py',
            'javascript', 'js', 'node'     => '.js',
            'typescript', 'ts'             => '.ts',
            'java'                         => '.java',
            'c'                            => '.c',
            'c++'                          => '.cpp',
            'csharp', 'c#', 'dotnet'       => '.cs',
            'rust'                         => '.rs',
            'go'                           => '.go',
            'php'                          => '.php',
            'ruby'                         => '.rb',
            'bash', 'sh', 'shell'          => '.sh',
            'kotlin'                       => '.kt',
            'swift'                        => '.swift',
            'r'                            => '.r',
            'perl'                         => '.pl',
            'lua'                          => '.lua',
            'haskell'                      => '.hs',
            'scala'                        => '.scala',
            'dart'                         => '.dart',
            'elixir'                       => '.ex',
            'erlang'                       => '.erl',
            'clojure'                      => '.clj',
            'fsharp', 'f#'                 => '.fs',
            'ocaml'                        => '.ml',
            'nim'                          => '.nim',
            'zig'                          => '.zig',
            'assembly', 'asm', 'nasm'      => '.asm',
            default                        => '.txt',
        };
    }
}
