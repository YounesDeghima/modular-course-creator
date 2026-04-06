<?php
namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class eventcontroller extends Controller
{
    // Shared logic — fetch events for a user
    private function getEvents($userId)
    {
        return Event::where('visibility', 'global')
            ->orWhere(fn($q) => $q->where('visibility','personal')->where('user_id', $userId))
            ->orderBy('start_date')
            ->get();
    }

    // ── User calendar ──
    public function userIndex()
    {
        $user   = Auth::user();
        $events = $this->getEvents($user->id);

        return view('pages.user.calendar', [
            'name'   => $user->name,
            'email'  => $user->email,
            'id'     => $user->id,
            'events' => $events,
        ]);
    }

    // ── Admin calendar ──
    public function adminIndex()
    {
        $admin  = Auth::user();
        $events = event::orderBy('start_date')->get();

        return view('pages.admin.calendar', [
            'name'   => $admin->name,
            'email'  => $admin->email,
            'id'     => $admin->id,
            'events' => $events,
        ]);
    }

    // ── Store (both admin global + user personal) ──
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'type'        => 'required|in:exam,vacation,project,assignment,personal',
            'visibility'  => 'required|in:global,personal',
        ]);

        // Only admins can create global events
        if ($validated['visibility'] === 'global' && $user->role !== 'admin') {
            $validated['visibility'] = 'personal';
        }

        $validated['user_id'] = $user->id;
        $event = event::create($validated);

        return response()->json($event);
    }

    // ── Update ──
    public function update(Request $request, event $event)
    {
        $user = Auth::user();

        // Users can only edit their own personal events
        if ($user->role !== 'admin' && $event->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'type'        => 'required|in:exam,vacation,project,assignment,personal',
            'visibility'  => 'required|in:global,personal',
        ]);

        if ($validated['visibility'] === 'global' && $user->role !== 'admin') {
            $validated['visibility'] = 'personal';
        }

        $event->update($validated);
        return response()->json($event);
    }

    // ── Destroy ──
    public function destroy(Event $event)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $event->user_id !== $user->id) abort(403);
        $event->delete();
        return response()->json(['ok' => true]);
    }
}
