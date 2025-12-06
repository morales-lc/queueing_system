<?php

namespace App\Http\Controllers;

use App\Models\QueueTicket;
use App\Events\TicketUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Mike42\Escpos\EscposImage;

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

        if (config('app.printer_enabled', false)) {
            $this->printTicket($ticket);
        }

        return redirect()->route('kiosk.ticket', $ticket);
    }

    public function showTicket(QueueTicket $ticket)
    {
        return view('kiosk.ticket', ['ticket' => $ticket]);
    }

    // Print ticket using ESC/POS
    protected function printTicket(QueueTicket $ticket)
    {
        try {
            Log::info('Starting print job for ticket: ' . $ticket->code);

            $printerPath = "smb://localhost/EPSONReceipt";
            $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($printerPath);

            $printer = new \Mike42\Escpos\Printer($connector);

            // Load and print logo image centered
            $logoPath = public_path('images/Lourdes_final.png');

            if (file_exists($logoPath)) {
                try {
                    $manager = new ImageManager(new Driver());
                    $img = $manager->read($logoPath);

                    $img->scale(width: 120);
                    $img->greyscale();
                    $img->contrast(20);

                    $tempPath = sys_get_temp_dir() . '/receipt_logo_' . time() . '.png';
                    $img->save($tempPath);

                    $escposImg = EscposImage::load($tempPath, false);

                    $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
                    $printer->graphics($escposImg);

                    @unlink($tempPath);
                } catch (\Exception $e) {
                    Log::warning('Logo print failed: ' . $e->getMessage());
                }
            }

            // CENTER ALL TEXT
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);

            $printer->text("\n");
            $printer->text("Your Queue Code\n");


            // BIG + BOLD QUEUE CODE
            // ULTRA BIG + ULTRA BOLD QUEUE CODE
            $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);

            // Increase font size (CHANGE 4,4 to 6,6 or 8,8 if you want even bigger)
            $printer->setTextSize(4, 4);
            $printer->setEmphasis(true); // bold ON

            $printer->text($ticket->code . "\n");

            // Reset back to normal
            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);


            $printer->text($ticket->created_at->format('M. j, Y h:i A') . "\n");


            $printer->selectPrintMode(\Mike42\Escpos\Printer::MODE_EMPHASIZED);
            $printer->text("Service: " . ucfirst($ticket->service_type) . "\n");
            $printer->selectPrintMode();

            $printer->feed(1);
            $printer->cut();
            $printer->close();

            Log::info('Print job completed successfully');
        } catch (\Throwable $e) {
            Log::error('Print failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
