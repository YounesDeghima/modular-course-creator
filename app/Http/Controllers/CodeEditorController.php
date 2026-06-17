<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CodeEditorController
 *
 * Passes a shared secret token to the blade so the browser can authenticate
 * with the Node PTY WebSocket server (pty-server/server.js).
 *
 * All code execution happens inside the Node server — this controller only
 * handles page rendering and token delivery.
 *
 * Required .env values:
 *   PTY_SECRET=<same random hex string set as PTY_SECRET when starting server.js>
 *   PTY_URL=ws://127.0.0.1:4000
 *
 * Required config/services.php entries:
 *   'pty' => [
 *       'secret' => env('PTY_SECRET', ''),
 *       'url'    => env('PTY_URL', 'ws://127.0.0.1:4000'),
 *   ],
 */
class CodeEditorController extends Controller
{
    /**
     * Returns the PTY secret from config.
     * This is passed to the blade and used by the browser as a WebSocket token.
     *
     * The Node server validates the incoming token === PTY_SECRET on the
     * WebSocket upgrade request. This keeps unauthenticated browsers out.
     */
    private function ptyToken(): string
    {
        return config('services.pty.secret', '');
    }

    /**
     * Returns the WebSocket base URL from config.
     * e.g. ws://127.0.0.1:4000
     *
     * The blade injects this as window.__PTY_BASE__ and app.js appends
     * the token as a query param: ws://127.0.0.1:4000?token=<secret>
     */
    private function ptyUrl(): string
    {
        return config('services.pty.url', 'ws://127.0.0.1:4000');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Editor pages
    // ──────────────────────────────────────────────────────────────────────────

    /** Admin standalone editor */
    public function adminEditor()
    {
        $user = Auth::user();
        return view('pages.shared.code-editor', [
            'name'     => $user?->name  ?? 'Guest',
            'email'    => $user?->email ?? '',
            'id'       => $user?->id    ?? null,
            'ptyToken' => $this->ptyToken(),
            'ptyUrl'   => $this->ptyUrl(),
        ]);
    }

    /** Student standalone editor */
    public function userEditor()
    {
        $user = Auth::user();
        return view('pages.shared.code-editor', [
            'name'     => $user?->name  ?? 'Guest',
            'email'    => $user?->email ?? '',
            'id'       => $user?->id    ?? null,
            'ptyToken' => $this->ptyToken(),
            'ptyUrl'   => $this->ptyUrl(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Legacy Piston endpoints — no longer used, safe to remove
    // ──────────────────────────────────────────────────────────────────────────

    public function runtimes()
    {
        return response()->json(['message' => 'Piston runtimes endpoint — not used by interactive editor.']);
    }

    public function execute(Request $request)
    {
        return response()->json(['message' => 'Interactive execution is now handled via WebSocket PTY server.'], 410);
    }

    public function judge(Request $request)
    {
        return response()->json(['message' => 'Judge mode is not yet implemented on the new PTY backend.'], 501);
    }
}
