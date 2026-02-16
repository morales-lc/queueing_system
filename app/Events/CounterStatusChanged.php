<?php

namespace App\Events;

use App\Models\Counter;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CounterStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $counter;
    public $status;

    public function __construct(Counter $counter, string $status)
    {
        $this->counter = $counter;
        $this->status = $status; // 'available' or 'unavailable'
    }

    public function broadcastOn()
    {
        return new Channel('counter.status');
    }

    public function broadcastAs()
    {
        return 'counter.status.changed';
    }

    public function broadcastWith()
    {
        return [
            'counter_id' => $this->counter->id,
            'counter_name' => $this->counter->name,
            'counter_type' => $this->counter->type,
            'status' => $this->status,
            'claimed' => $this->counter->claimed,
        ];
    }
}
