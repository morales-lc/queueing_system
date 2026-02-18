<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\QueueTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\TicketUpdated;
use Illuminate\Support\Facades\Auth;

class CounterController extends Controller
{
    protected function todayTickets()
    {
        return QueueTicket::whereDate('created_at', today());
    }

    protected function getLastCalledTicket(string $serviceType): ?QueueTicket
    {
        return $this->todayTickets()->where('service_type', $serviceType)
            ->whereIn('status', ['serving', 'done', 'on_hold'])
            ->where('called_times', '>', 0)
            ->latest('updated_at')
            ->first();
    }

    protected function getAlternatingStartBucket(string $serviceType, ?QueueTicket $nextStudent, ?QueueTicket $nextPriority): string
    {
        if (!$nextStudent) {
            return 'priority';
        }

        if (!$nextPriority) {
            return 'student';
        }

        $lastCalled = $this->getLastCalledTicket($serviceType);

        if (!$lastCalled) {
            return $nextStudent->created_at->lte($nextPriority->created_at) ? 'student' : 'priority';
        }

        return $lastCalled->priority === 'student' ? 'priority' : 'student';
    }

    protected function getNextPendingTicketAlternating(string $serviceType): ?QueueTicket
    {
        $nextStudent = $this->todayTickets()->where('service_type', $serviceType)
            ->where('status', 'pending')
            ->where('priority', 'student')
            ->orderBy('created_at')
            ->first();

        $nextPriority = $this->todayTickets()->where('service_type', $serviceType)
            ->where('status', 'pending')
            ->where('priority', '!=', 'student')
            ->orderBy('created_at')
            ->first();

        if (!$nextStudent && !$nextPriority) {
            return null;
        }

        if (!$nextStudent) {
            return $nextPriority;
        }

        if (!$nextPriority) {
            return $nextStudent;
        }

        $startBucket = $this->getAlternatingStartBucket($serviceType, $nextStudent, $nextPriority);

        return $startBucket === 'student' ? $nextStudent : $nextPriority;
    }

    protected function getPendingQueueAlternating(string $serviceType)
    {
        $studentQueue = $this->todayTickets()->where('service_type', $serviceType)
            ->where('status', 'pending')
            ->where('priority', 'student')
            ->orderBy('created_at')
            ->get();

        $priorityQueue = $this->todayTickets()->where('service_type', $serviceType)
            ->where('status', 'pending')
            ->where('priority', '!=', 'student')
            ->orderBy('created_at')
            ->get();

        if ($studentQueue->isEmpty() && $priorityQueue->isEmpty()) {
            return collect();
        }

        if ($studentQueue->isEmpty()) {
            return $priorityQueue;
        }

        if ($priorityQueue->isEmpty()) {
            return $studentQueue;
        }

        $startBucket = $this->getAlternatingStartBucket($serviceType, $studentQueue->first(), $priorityQueue->first());
        $result = collect();
        $turn = $startBucket;

        while ($studentQueue->isNotEmpty() || $priorityQueue->isNotEmpty()) {
            if ($turn === 'student') {
                if ($studentQueue->isNotEmpty()) {
                    $result->push($studentQueue->shift());
                    $turn = 'priority';
                } elseif ($priorityQueue->isNotEmpty()) {
                    $result->push($priorityQueue->shift());
                }
            } else {
                if ($priorityQueue->isNotEmpty()) {
                    $result->push($priorityQueue->shift());
                    $turn = 'student';
                } elseif ($studentQueue->isNotEmpty()) {
                    $result->push($studentQueue->shift());
                }
            }
        }

        return $result;
    }

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
        
        $queue = $this->getPendingQueueAlternating($counter->type);

        $onHold = $this->todayTickets()->where('service_type', $counter->type)
            ->where(function ($query) {
                $query->where('status', 'on_hold')
                    ->orWhere(function ($q) {
                        $q->where('status', 'serving')
                            ->whereNotNull('hold_count')
                            ->where('hold_count', '>', 0);
                    });
            })
            ->orderBy('updated_at', 'asc')
            ->get();

        $nowServing = $this->todayTickets()->where('counter_id', $counter->id)
            ->where('status', 'serving')
            ->latest()
            ->first();

        return view('operator.counter', compact('counter', 'queue', 'onHold', 'nowServing'));
    }

    public function next(Counter $counter)
    {
        // Server-side rate limiting: prevent rapid clicks (10 second cooldown)
        $lastNextTime = session('last_next_time_' . $counter->id);
        $now = now()->timestamp;
        
        if ($lastNextTime && ($now - $lastNextTime) < 10) {
            return redirect()->route('counter.show', $counter)->withErrors([
                'rate_limit' => 'Please wait before calling the next ticket.'
            ]);
        }
        
        // Update last action time
        session(['last_next_time_' . $counter->id => $now]);
        
        $currentTicket = null;
        
        // Use transaction to ensure both updates complete before broadcasting
        DB::transaction(function () use ($counter, &$nextTicket, &$currentTicket) {
            // Mark currently serving ticket as done (transaction completed)
            $currentTicket = QueueTicket::where('counter_id', $counter->id)
                ->where('status', 'serving')
                ->first();

            if ($currentTicket) {
                $currentTicket->status = 'done';
                $currentTicket->counter_id = null;
                $currentTicket->save();
            }

            // Get next pending ticket using alternating student/priority strategy
            $nextTicket = $this->getNextPendingTicketAlternating($counter->type);

            if ($nextTicket) {
                // Ensure same ticket not served by another counter
                if ($nextTicket->status === 'serving' && $nextTicket->counter_id !== $counter->id) {
                    throw new \Exception('Ticket already serving in another counter.');
                }

                $nextTicket->status = 'serving';
                $nextTicket->counter_id = $counter->id;
                $nextTicket->called_times = ($nextTicket->called_times ?? 0) + 1;
                $nextTicket->save();
            }
        });

        // Broadcast events after transaction completes
        if ($currentTicket) {
            event(new TicketUpdated('done', $currentTicket));
        }

        if (!$nextTicket) {
            // No more tickets - just return without serving anything
            return redirect()->route('counter.show', $counter)->with('status', 'No pending ticket.');
        }

        event(new TicketUpdated('serving', $nextTicket));

        // Auto-remove oldest on-hold after every 3 Next presses (based on total calls)
        static $nextPressCount = 0;
        $nextPressCount++;
        
        $removed = false;
        if ($nextPressCount % 3 === 0) {
            $oldestHold = $this->todayTickets()->where('service_type', $counter->type)
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
        // Server-side rate limiting: prevent rapid clicks (10 second cooldown)
        $lastHoldTime = session('last_hold_time_' . $counter->id);
        $now = now()->timestamp;
        
        if ($lastHoldTime && ($now - $lastHoldTime) < 10) {
            return redirect()->route('counter.show', $counter)->withErrors([
                'rate_limit' => 'Please wait before putting a ticket on hold.'
            ]);
        }
        
        //update last action time
        session(['last_hold_time_' . $counter->id => $now]);
        
        if ($ticket->status === 'serving' && $ticket->counter_id === $counter->id && $ticket->created_at->isToday()) {
            //Mark current ticket as on_hold
            $ticket->status = 'on_hold';
            $ticket->hold_count = ($ticket->hold_count ?? 0) + 1;
            $ticket->counter_id = null; // Release from this counter
            $ticket->save();
            event(new TicketUpdated('on_hold', $ticket));

            // Automatically serve the next pending ticket using alternating strategy
            $nextTicket = $this->getNextPendingTicketAlternating($counter->type);

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
        if (in_array($ticket->status, ['on_hold', 'serving'], true) && $ticket->service_type === $counter->type && $ticket->created_at->isToday()) {
            // Use transaction to handle both the current serving ticket and the called ticket
            DB::transaction(function () use ($counter, $ticket) {
                // First, handle any currently serving ticket at this counter
                $currentlyServing = QueueTicket::where('counter_id', $counter->id)
                    ->where('status', 'serving')
                    ->where('id', '!=', $ticket->id)
                    ->first();
                
                if ($currentlyServing) {
                    // Put the currently serving ticket back to pending
                    $currentlyServing->status = 'pending';
                    $currentlyServing->counter_id = null;
                    $currentlyServing->save();
                }
                
                // Now serve the called ticket
                $ticket->status = 'serving';
                $ticket->counter_id = $counter->id;
                $ticket->called_times = ($ticket->called_times ?? 0) + 1;
                $ticket->save();
            });
            
            event(new TicketUpdated('serving', $ticket));
        }
        return redirect()->route('counter.show', $counter);
    }

    public function removeHold(Counter $counter, QueueTicket $ticket)
    {
        if (in_array($ticket->status, ['on_hold', 'serving'], true) && $ticket->service_type === $counter->type && $ticket->created_at->isToday()) {
            $ticket->status = 'done';
            $ticket->save();
            event(new TicketUpdated('done', $ticket));
        }
        return redirect()->route('counter.show', $counter);
    }
}
