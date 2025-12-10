<?php

namespace App\Http\Controllers;

use App\Models\QueueTicket;
use App\Events\TicketUpdated;
use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KioskController extends Controller
{
    public function index()
    {
        return view('kiosk.index');
    }

    public function chooseService(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'required|in:cashier,registrar',
        ]);

        return view('kiosk.priority', ['service' => $validated['service_type']]);
    }

    public function choosePriority(Request $request)
    {
        $validated = $request->validate([
            'service' => 'required|in:cashier,registrar',
            'priority' => 'required|in:pwd_senior_pregnant,student,parent',
        ]);

        return view('kiosk.confirm', $validated);
    }

    public function issueTicket(Request $request)
    {
        $validated = $request->validate([
            'service' => 'required|in:cashier,registrar',
            'priority' => 'required|in:pwd_senior_pregnant,student,parent',
        ]);

        // Check if print server is available (optional - don't block ticket generation)
        $printService = new PrintService();
        if ($printService->isEnabled() && !$printService->checkHealth()) {
            Log::warning('Print server is not available, but continuing with ticket generation');
        }

        // Generate prefix (e.g., C-S, R-P, etc.)
        $prefix = strtoupper(substr($validated['service'], 0, 1))
            . strtoupper(substr($validated['priority'], 0, 1));

        // Reset daily: count only today's tickets for this service
        $countToday = QueueTicket::where('service_type', $validated['service'])
            ->whereDate('created_at', today())
            ->count() + 1;

        // Create the sequence number
        $sequence = str_pad((string)$countToday, 3, '0', STR_PAD_LEFT);

        // Final Code (CS-001)
        $code = $prefix . '-' . $sequence;

        // Save ticket
        $ticket = QueueTicket::create([
            'code' => $code,
            'service_type' => $validated['service'],
            'priority' => $validated['priority'],
        ]);

        event(new TicketUpdated('created', $ticket));

        // Try to print (non-blocking)
        if ($printService->isEnabled()) {
            $printService->printTicket($ticket);
        }

        return redirect()->route('kiosk.ticket', $ticket);
    }

    public function showTicket(QueueTicket $ticket)
    {
        return view('kiosk.ticket', ['ticket' => $ticket]);
    }
}
