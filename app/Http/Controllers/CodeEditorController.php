<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/**
 * CodeEditorController
 *
 * Code execution is now handled entirely by the Node PTY server (pty-server/server.js).
 * This controller's only execution-related job is to pass a one-time signed token
 * to the blade so the browser can authenticate with the WebSocket server.
 *
 * The Piston execute() and runtimes() methods are kept below but are no longer
 * called by the interactive editor. You can remove them when ready.
 */
class CodeEditorController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    //  Token helper
    //  Generates a short-lived HMAC token the blade passes as ?token= on the
    //  WebSocket URL.  The PTY server validates it against the same PTY_SECRET.
    //
    //  We do NOT send PTY_SECRET itself to the browser — we send an HMAC
    //  of (userId + expiry) so each token is user-scoped and expires in 5 min.
    //  The Node server verifies the raw secret; this HMAC adds a second layer
    //  so even a leaked token can't be replayed after expiry.
    //
    //  For simplicity in a local/intranet deployment you can also just pass
    //  PTY_SECRET directly (see comment in ptyToken()).  Either way, PTY_SECRET
    //  never appears in any HTTP response body — only the derived token does.
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build a short-lived token the browser uses to authenticate with the
     * PTY WebSocket server.
     *
     * Format:  HMAC-SHA256( secret, "{userId}:{expiresAt}" ) + ":{expiresAt}"
     * The Node server verifies this in verifyClient() — see server.js note.
     *
     * NOTE: if you prefer the simpler approach of just passing PTY_SECRET
     * directly (fine for a local school network), replace this method body with:
     *     return config('services.pty.secret');
     * and remove the HMAC verification changes from server.js.
     */
    private function ptyToken(): string
    {
        $secret    = config('services.pty.secret');   // reads PTY_SECRET from .env
        $userId    = Auth::id() ?? 'guest';
        $expiresAt = time() + 300;                    // 5 minutes
        $payload   = "{$userId}:{$expiresAt}";
        $hmac      = hash_hmac('sha256', $payload, $secret);

        return "{$hmac}:{$expiresAt}";
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Editor pages
    // ──────────────────────────────────────────────────────────────────────────

    /** Admin standalone editor */
    public function adminEditor()
    {
        $user = Auth::user();
        return view('pages.admin.code-editor', [
            'name'     => $user?->name  ?? 'Guest',
            'email'    => $user?->email ?? '',
            'id'       => $user?->id    ?? null,
            'ptyToken' => $this->ptyToken(),
            'ptyUrl'   => config('services.pty.url', 'ws://127.0.0.1:4000'),
        ]);
    }

    /** Student standalone editor */
    public function userEditor()
    {
        $user = Auth::user();
        return view('pages.user.code-editor', [
            'name'     => $user?->name  ?? 'Guest',
            'email'    => $user?->email ?? '',
            'id'       => $user?->id    ?? null,
            'ptyToken' => $this->ptyToken(),
            'ptyUrl'   => config('services.pty.url', 'ws://127.0.0.1:4000'),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Legacy Piston endpoints — kept for judge mode, safe to remove otherwise
    // ──────────────────────────────────────────────────────────────────────────

    public function runtimes()
    {
        // Piston no longer used for interactive runs.
        // Return a static list or remove this route entirely.
        return response()->json(['message' => 'Piston runtimes endpoint — not used by interactive editor.']);
    }

    public function execute(Request $request)
    {
        return response()->json(['message' => 'Interactive execution is now handled via WebSocket PTY server.'], 410);
    }

    public function judge(Request $request)
    {
        // Judge mode can be re-implemented using the PTY server later.
        return response()->json(['message' => 'Judge mode is not yet implemented on the new PTY backend.'], 501);
    }
}
