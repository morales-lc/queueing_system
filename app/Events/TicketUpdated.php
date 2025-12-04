<?php

namespace App\Events;

use App\Models\QueueTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type; // created|serving|on_hold|done
    public QueueTicket $ticket;

    public function __construct(string $type, QueueTicket $ticket)
    {
        $this->type = $type;
        $this->ticket = $ticket;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('queue.' . $this->ticket->service_type);
    }

    public function broadcastAs(): string
    {
        return 'ticket.' . $this->type;
    }
}
