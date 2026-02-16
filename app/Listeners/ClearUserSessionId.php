<?php

namespace App\Listeners;

use Illuminate\Session\Events\SessionDestroyed;
use App\Models\User;

class ClearUserSessionId
{
    /**
     * Handle the event.
     */
    public function handle(SessionDestroyed $event)
    {
        // Find user by session_id and clear session_id and is_logged_in
        if (!empty($event->sessionId)) {
            $user = User::where('session_id', $event->sessionId)->first();
            if ($user) {
                $user->session_id = null;
                $user->is_logged_in = false;
                $user->save();
            }
        }
    }
}
