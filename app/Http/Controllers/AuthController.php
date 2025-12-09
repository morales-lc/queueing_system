<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Events\CounterStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->counter_id) {
                return redirect()->route('counter.show', $user->counter_id);
            }
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = Auth::user();
            $currentSessionId = $request->session()->getId();
            
            // Check if user is already logged in from another session
            if ($user->session_id && $user->session_id !== $currentSessionId) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return back()->withErrors([
                    'email' => 'This account is already logged in on another device. Please logout from the other device first.'
                ])->onlyInput('email');
            }
            
            $request->session()->regenerate();
            
            // Store current session ID
            $user->session_id = $currentSessionId;
            $user->save();
            
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
            
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is not assigned to any counter.'
            ]);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
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

        // Clear session ID
        if ($user) {
            $user->session_id = null;
            $user->save();
        }

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
