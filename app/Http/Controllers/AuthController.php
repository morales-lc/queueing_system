<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\User;
use App\Events\CounterStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role === 'admin') {
                return redirect()->route('admin.sessions.index');
            }

            if ($user->counter_id) {
                return redirect()->route('counter.show', $user->counter_id);
            }
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        // Check if user already has an active session
        $user = User::where('username', $credentials['username'])->first();
        if ($user) {
            $activeSessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('last_activity', '>', now()->subMinutes(config('session.lifetime', 120))->timestamp)
                ->count();

            if ($activeSessions > 0) {
                return back()->withErrors([
                    'username' => 'This account is already logged in on another device. Please log out from the other device first.',
                ])->onlyInput('username');
            }
        }

        // Attempt login
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->role === 'admin') {
                return redirect()->route('admin.sessions.index');
            }

            // Claim the counter automatically
            if ($user->counter_id) {
                /** @var Counter $counter */
                $counter = $user->counter;
                $counter->claimed = true;
                $counter->save();

                // Broadcast counter availability change
                event(new CounterStatusChanged($counter, 'available'));

                return redirect()->route('counter.show', $user->counter_id);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'username' => 'Your account is not assigned to any counter.'
            ]);
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        // If no authenticated user, just redirect
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Release the counter
        if ($user && $user->counter_id) {
            /** @var Counter|null $counter */
            $counter = $user->counter;
            if ($counter) {
                $counter->claimed = false;
                $counter->save();

                // Broadcast counter availability change
                event(new CounterStatusChanged($counter, 'unavailable'));
            }
        }

        // 

        Auth::logout();

        // If this is a beacon/unload-triggered logout, avoid session invalidation
        if ($request->has('beacon')) {
            return response()->noContent();
        }

        // Normal logout: invalidate and redirect
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
