<?php

namespace App\Http\Controllers\Admin;

use App\Events\CounterStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            abort(403);
        }
    }

    public function index()
    {
        $this->ensureAdmin();

        $activeSince = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;

        $sessions = DB::table('sessions as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->leftJoin('counters as c', 'c.id', '=', 'u.counter_id')
            ->whereNotNull('s.user_id')
            ->where('s.last_activity', '>', $activeSince)
            ->select(
                's.id as session_id',
                's.user_id',
                's.ip_address',
                's.user_agent',
                's.last_activity',
                'u.username',
                'u.role',
                'u.counter_id',
                'c.name as counter_name',
                'c.type as counter_type'
            )
            ->orderByDesc('s.last_activity')
            ->get()
            ->map(function ($row) {
                $row->last_activity_human = Carbon::createFromTimestamp((int) $row->last_activity)->diffForHumans();
                $row->last_activity_at = Carbon::createFromTimestamp((int) $row->last_activity)->toDateTimeString();

                return $row;
            });

        $stats = [
            'total_active_sessions' => $sessions->count(),
            'users_online' => $sessions->pluck('user_id')->unique()->count(),
            'cashier_online' => $sessions->where('role', 'cashier')->count(),
            'registrar_online' => $sessions->where('role', 'registrar')->count(),
            'admin_online' => $sessions->where('role', 'admin')->count(),
        ];

        return view('admin.sessions.index', compact('sessions', 'stats'));
    }

    public function destroy(string $sessionId)
    {
        $this->ensureAdmin();

        $session = DB::table('sessions')->where('id', $sessionId)->first();

        if (!$session) {
            return back()->withErrors(['session' => 'Session not found or already expired.']);
        }

        if ((int) $session->user_id === (int) Auth::id()) {
            return back()->withErrors(['session' => 'You cannot terminate your own current session from this screen.']);
        }

        DB::transaction(function () use ($session, $sessionId) {
            $this->releaseCounterIfNeeded((int) $session->user_id);
            DB::table('sessions')->where('id', $sessionId)->delete();
        });

        return back()->with('status', 'Session terminated successfully.');
    }

    public function destroyUserSessions(User $user)
    {
        $this->ensureAdmin();

        if ((int) $user->id === (int) Auth::id()) {
            return back()->withErrors(['session' => 'You cannot terminate your own current session from this screen.']);
        }

        $deleted = 0;

        DB::transaction(function () use (&$deleted, $user) {
            $deleted = DB::table('sessions')->where('user_id', $user->id)->delete();
            $this->releaseCounterIfNeeded((int) $user->id);
        });

        return back()->with('status', "Terminated {$deleted} session(s) for {$user->username}.");
    }

    private function releaseCounterIfNeeded(int $userId): void
    {
        $user = User::with('counter')->find($userId);

        if (!$user || !$user->counter_id) {
            return;
        }

        /** @var Counter|null $counter */
        $counter = $user->counter;

        if (!$counter || !$counter->claimed) {
            return;
        }

        $counter->claimed = false;
        $counter->save();

        event(new CounterStatusChanged($counter, 'unavailable'));
    }
}
