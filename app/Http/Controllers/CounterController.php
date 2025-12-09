<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\QueueTicket;
use Illuminate\Http\Request;
use Illuminate\Support\DB;
use App\Events\TicketUpdated;
use Illuminate\Support\Facades\Auth;

class CounterController extends Controller
{
    public function select()
    {
        $userRole = Auth::user()->role;
        
        // Only show counters matching user's role
        $counters = Counter::where('type', $userRole)
            ->orderBy('name')
            ->get();
        
        return view('operator.select', compact('counters', 'userRole'));
    }

    public function claim(Request $request)
    {
        $validated = $request->validate([
            'counter_id' => 'required|exists:counters,id',
        ]);

        $counter = Counter::findOrFail($validated['counter_id']);
        
        // Check if counter type matches user's role
        if ($counter->type !== Auth::user()->role) {
            return back()->withErrors(['counter' => 'You cannot claim this counter type.']);
        }
        
        if ($counter->claimed) {
            return back()->withErrors(['counter' => 'Counter already in use.']);
        }
        
        $counter->claimed = true;
        $counter->save();
        return redirect()->route('counter.show', $counter);
    }

    public function release(Request $request)
    {
        $user = Auth::user();
        
        if ($user && $user->counter_id) {
            $counter = $user->counter;
            if ($counter) {
                $counter->claimed = false;
                $counter->save();
            }
        }
        
        return redirect()->route('login');
    }

    public function show(Counter $counter)
    {
        // Verify user has access to this counter
        $user = Auth::user();
        if (!$user || $user->counter_id !== $counter->id) {
            abort(403, 'Unauthorized access to this counter.');
        }
        
        $queue = QueueTicket::where('service_type', $counter->type)
            ->where('status', 'pending')
            ->orderByRaw("FIELD(priority, 'pwd_senior_pregnant','student','parent')")
            ->orderBy('created_at')
            ->limit(5)
            ->get();

        $onHold = QueueTicket::where('service_type', $counter->type)
            ->where('status', 'on_hold')
            ->orderBy('updated_at', 'asc')
            ->get();

        $nowServing = QueueTicket::where('counter_id', $counter->id)
            ->where('status', 'serving')
            ->latest()
            ->first();

        return view('operator.counter', compact('counter', 'queue', 'onHold', 'nowServing'));
    }

    public function next(Counter $counter)
    {
        // Mark currently serving ticket as done (transaction completed)
        $currentTicket = QueueTicket::where('counter_id', $counter->id)
            ->where('status', 'serving')
            ->first();

        if ($currentTicket) {
            $currentTicket->status = 'done';
            $currentTicket->counter_id = null;
            $currentTicket->save();
            event(new TicketUpdated('done', $currentTicket));
        }

        // Get next pending ticket
        $nextTicket = QueueTicket::where('service_type', $counter->type)
            ->where('status', 'pending')
            ->orderByRaw("FIELD(priority, 'pwd_senior_pregnant','student','parent')")
            ->orderBy('created_at')
            ->first();

        if (!$nextTicket) {
            // No more tickets - just return without serving anything
            return redirect()->route('counter.show', $counter)->with('status', 'No pending ticket.');
        }

        // Ensure same ticket not served by another counter
        if ($nextTicket->status === 'serving' && $nextTicket->counter_id !== $counter->id) {
            return back()->withErrors(['ticket' => 'Ticket already serving in another counter.']);
        }

        $nextTicket->status = 'serving';
        $nextTicket->counter_id = $counter->id;
        $nextTicket->called_times = ($nextTicket->called_times ?? 0) + 1;
        $nextTicket->save();
        event(new TicketUpdated('serving', $nextTicket));

        // Auto-remove oldest on-hold after every 3 Next presses (based on total calls)
        static $nextPressCount = 0;
        $nextPressCount++;
        
        $removed = false;
        if ($nextPressCount % 3 === 0) {
            $oldestHold = QueueTicket::where('service_type', $counter->type)
                ->where('status', 'on_hold')
                ->orderBy('updated_at', 'asc')
                ->first();
            if ($oldestHold) {
                $oldestHold->status = 'done';
                $oldestHold->save();
                event(new TicketUpdated('done', $oldestHold));
                $removed = true;
            }
        }

        return redirect()->route('counter.show', $counter)->with('status', $removed ? 'Oldest on-hold removed.' : 'Serving next.');
    }

    public function hold(Counter $counter, QueueTicket $ticket)
    {
        if ($ticket->status === 'serving' && $ticket->counter_id === $counter->id) {
            // Mark current ticket as on_hold
            $ticket->status = 'on_hold';
            $ticket->hold_count = ($ticket->hold_count ?? 0) + 1;
            $ticket->counter_id = null; // Release from this counter
            $ticket->save();
            event(new TicketUpdated('on_hold', $ticket));

            // Automatically serve the next pending ticket
            $nextTicket = QueueTicket::where('service_type', $counter->type)
                ->where('status', 'pending')
                ->orderByRaw("FIELD(priority, 'pwd_senior_pregnant','student','parent')")
                ->orderBy('created_at')
                ->first();

            if ($nextTicket) {
                $nextTicket->status = 'serving';
                $nextTicket->counter_id = $counter->id;
                $nextTicket->called_times = ($nextTicket->called_times ?? 0) + 1;
                $nextTicket->save();
                event(new TicketUpdated('serving', $nextTicket));
            }
        }
        return redirect()->route('counter.show', $counter);
    }

    public function callAgain(Counter $counter, QueueTicket $ticket)
    {
        if ($ticket->status === 'on_hold' && $ticket->service_type === $counter->type) {
            $ticket->status = 'serving';
            $ticket->counter_id = $counter->id;
            $ticket->called_times = ($ticket->called_times ?? 0) + 1;
            $ticket->save();
            event(new TicketUpdated('serving', $ticket));
        }
        return redirect()->route('counter.show', $counter);
    }

    public function removeHold(Counter $counter, QueueTicket $ticket)
    {
        if ($ticket->status === 'on_hold' && $ticket->service_type === $counter->type) {
            $ticket->status = 'done';
            $ticket->save();
            event(new TicketUpdated('done', $ticket));
        }
        return redirect()->route('counter.show', $counter);
    }
}
