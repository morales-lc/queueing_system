<?php

use App\Models\Counter;
use App\Models\QueueTicket;
use Illuminate\Support\Facades\Event;
use App\Events\TicketUpdated;

test('kiosk can issue a ticket', function () {
    Event::fake();
    
    $response = $this->post(route('kiosk.issue'), [
        'service' => 'cashier',
        'priority' => 'student',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('queue_tickets', [
        'service_type' => 'cashier',
        'priority' => 'student',
        'status' => 'pending',
    ]);

    Event::assertDispatched(TicketUpdated::class);
});

test('counter can be claimed and released', function () {
    $counter = Counter::factory()->create(['type' => 'cashier', 'name' => '1', 'claimed' => false]);

    $this->post(route('counter.claim'), ['counter_id' => $counter->id]);
    $this->assertDatabaseHas('counters', ['id' => $counter->id, 'claimed' => true]);

    $this->post(route('counter.release'), ['counter_id' => $counter->id]);
    $this->assertDatabaseHas('counters', ['id' => $counter->id, 'claimed' => false]);
});

test('counter serves next ticket and broadcasts', function () {
    Event::fake();
    
    $counter = Counter::factory()->create(['type' => 'cashier', 'name' => '1']);
    $ticket = QueueTicket::factory()->create([
        'service_type' => 'cashier',
        'priority' => 'student',
        'status' => 'pending',
    ]);

    $this->post(route('counter.next', $counter->id));

    $ticket->refresh();
    expect($ticket->status)->toBe('serving');
    expect($ticket->counter_id)->toBe($counter->id);

    Event::assertDispatched(TicketUpdated::class, fn($e) => $e->type === 'serving');
});

test('counter can hold and call again', function () {
    Event::fake();
    
    $counter = Counter::factory()->create(['type' => 'cashier', 'name' => '1']);
    $ticket = QueueTicket::factory()->create([
        'service_type' => 'cashier',
        'status' => 'serving',
        'counter_id' => $counter->id,
    ]);

    // Hold
    $this->post(route('counter.hold', [$counter->id, $ticket->id]));
    $ticket->refresh();
    expect($ticket->status)->toBe('on_hold');

    // Call again
    $this->post(route('counter.callAgain', [$counter->id, $ticket->id]));
    $ticket->refresh();
    expect($ticket->status)->toBe('serving');

    Event::assertDispatched(TicketUpdated::class);
});

test('oldest on-hold is auto-removed after 3 nexts', function () {
    $counter = Counter::factory()->create(['type' => 'cashier', 'name' => '1']);
    $oldHold = QueueTicket::factory()->create([
        'service_type' => 'cashier',
        'status' => 'on_hold',
        'updated_at' => now()->subMinutes(10),
    ]);

    // Create 3 pending tickets and call next 3 times
    QueueTicket::factory()->count(3)->create([
        'service_type' => 'cashier',
        'status' => 'pending',
    ]);

    $this->post(route('counter.next', $counter->id));
    $this->post(route('counter.next', $counter->id));
    $this->post(route('counter.next', $counter->id));

    $oldHold->refresh();
    expect($oldHold->status)->toBe('done');
});
