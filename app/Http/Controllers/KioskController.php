<?php

namespace App\Http\Controllers;

use App\Models\QueueTicket;
use App\Events\TicketUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
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

        $prefix = strtoupper(substr($validated['service'], 0, 1)) . strtoupper(substr($validated['priority'], 0, 1));
        $sequence = str_pad((string)(QueueTicket::where('service_type', $validated['service'])->count() + 1), 3, '0', STR_PAD_LEFT);
        $code = $prefix . '-' . $sequence;

        $ticket = QueueTicket::create([
            'code' => $code,
            'service_type' => $validated['service'],
            'priority' => $validated['priority'],
        ]);

        event(new TicketUpdated('created', $ticket));

        // Print ticket if enabled
        if (config('app.printer_enabled', false)) {
            $this->printTicket($ticket);
        }

        return redirect()->route('kiosk.ticket', $ticket);
    }

    public function showTicket(QueueTicket $ticket)
    {
        return view('kiosk.ticket', ['ticket' => $ticket]);
    }

    protected function printTicket(QueueTicket $ticket)
    {
        try {
            $type = env('PRINTER_TYPE', 'windows');
            $target = env('PRINTER_TARGET', 'EPSON TM-T82II');

            if ($type === 'windows') {
                $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($target);
            } else {
                $host = $target;
                $port = (int)env('PRINTER_PORT', 9100);
                $connector = new \Mike42\Escpos\PrintConnectors\NetworkPrintConnector($host, $port);
            }

            $printer = new \Mike42\Escpos\Printer($connector);
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
            $printer->text("Queue Code\n");
            $printer->selectPrintMode(\Mike42\Escpos\Printer::MODE_DOUBLE_WIDTH);
            $printer->text($ticket->code . "\n");
            $printer->selectPrintMode();
            $printer->text($ticket->created_at->format('F j, Y g:i A') . "\n");
            $printer->text(ucfirst($ticket->service_type) . " - " . str_replace('_', ' ', ucfirst($ticket->priority)) . "\n");
            $printer->feed(2);
            $printer->cut();
            $printer->close();
        } catch (\Throwable $e) {
            Log::error('Print failed: ' . $e->getMessage());
        }
    }
}
