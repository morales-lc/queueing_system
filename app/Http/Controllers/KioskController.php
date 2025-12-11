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

        // If printing is enabled, ensure printer is online before generating a code
        // Skip printer check for HTTP printing (handled by remote print server)
        if (config('app.printer_enabled', false) && config('app.printer_type') !== 'http') {
            // Use the actual Windows printer name as reported by Get-Printer
            $printerShareName = 'EPSON TM-T82II Receipt';
            // Use strict check to prevent ticket generation when printer is disconnected
            if (!$this->isPrinterOnlineStrict($printerShareName)) {
                // Redirect to index with error message
                return redirect()->route('kiosk.index')
                    ->withErrors(['printer' => 'Printer is not connected. Please ask staff for assistance and try again once connected.']);
            }
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

        // Final Code ( CS-001)
        $code = $prefix . '-' . $sequence;

        // Save ticket
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

    // Print ticket using ESC/POS or HTTP
    protected function printTicket(QueueTicket $ticket)
    {
        try {
            Log::info('Starting print job for ticket: ' . $ticket->code);
            
            $printerType = config('app.printer_type', 'windows');
            
            // Use HTTP printing if configured
            if ($printerType === 'http') {
                return $this->printViaHttp($ticket);
            }
            
            // Original ESC/POS printing for Windows
            // Guard: Skip printing if printer is offline to avoid OS spooling backlog
            $printerShareName = 'EPSON TM-T82II Receipt'; // Windows printer name
            $smbPath = "smb://localhost/EPSONReceipt"; // Original working path

            // Strict check before sending any job to Windows spooler; prevents queue buildup
            if (!$this->isPrinterOnlineStrict($printerShareName)) {
                Log::warning('Printer appears offline. Skipping print for ticket: ' . $ticket->code);
                return; // Do not attempt to send job to spooler
            }

            // Try using original smb path first, then fall back to share name
            try {
                $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($smbPath);
            } catch (\Throwable $e) {
                Log::warning('SMB path connector failed, falling back to share name: ' . $e->getMessage());
                $connector = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($printerShareName);
            }

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

    // Print ticket via HTTP to remote print server (Windows 11 computer)
    protected function printViaHttp(QueueTicket $ticket)
    {
        try {
            $printerUrl = config('app.printer_target');
            
            if (!$printerUrl) {
                Log::error('PRINTER_TARGET not configured in .env');
                return;
            }
            
            Log::info('Sending print job via HTTP to: ' . $printerUrl);
            
            // Prepare print data
            $printData = [
                'code' => $ticket->code,
                'service' => ucfirst($ticket->service_type),
                'priority' => ucfirst(str_replace('_', ' ', $ticket->priority)),
                'time' => $ticket->created_at->format('M. j, Y h:i A'),
                'logo' => true,
            ];
            
            // Send HTTP request to Windows print server
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->post($printerUrl, $printData);
            
            if ($response->successful()) {
                Log::info('Print job sent successfully via HTTP');
                $responseData = $response->json();
                Log::info('Print server response: ' . json_encode($responseData));
            } else {
                Log::error('Print server returned error: ' . $response->status() . ' - ' . $response->body());
            }
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Cannot connect to print server: ' . $e->getMessage());
            Log::error('Make sure the print server is running on the Windows 11 computer');
        } catch (\Throwable $e) {
            Log::error('HTTP print failed: ' . $e->getMessage());
        }
    }

    // Check Windows printer status using PowerShell; returns true if not Offline
    protected function isPrinterOnline(string $printerName): bool
    {
        try {
            // Query printer status; be tolerant of environments where PowerShell or permissions block the call.
            // We'll treat failures as "online" to avoid false negatives at the kiosk.
            // Use single-quoted PHP string to avoid PHP interpolating PowerShell variables like $p
            $psCommand = '($p = Get-Printer -Name \'" . addslashes($printerName) . "\' -ErrorAction SilentlyContinue); if ($p) { if ($p.WorkOffline) { \"Offline\" } else { $p.PrinterStatus } } else { \"Unknown\" }';
            $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $psCommand . '"';

            $output = @shell_exec($cmd);
            if ($output === null) {
                // If we cannot query status, assume online to prevent blocking kiosk usage
                Log::warning('Printer status query returned null. Assuming printer is online.');
                return true;
            }

            $status = strtolower(trim($output));
            // Consider only explicit "offline" as offline; everything else is treated as online
            if ($status === 'offline') {
                return false;
            }

            // Some environments report "unknown" or numeric codes; treat them as online to avoid false blocks
            return true;
        } catch (\Throwable $e) {
            // On any exception, default to online to avoid blocking kiosk
            Log::warning('Printer status check failed: ' . $e->getMessage() . ' — assuming printer is online.');
            return true;
        }
    }

    // Strict variant used right before spooling: only proceeds when clearly online/idle/printing
    protected function isPrinterOnlineStrict(string $printerName): bool
    {
        try {
            // Check if shell_exec is available
            if (!function_exists('shell_exec')) {
                Log::warning('shell_exec is disabled. Cannot check printer status. Treating as online to allow printing.');
                return true; // Allow printing if we can't check status
            }

            $escapedName = addslashes($printerName);
            
            // Check 1: Get printer status
            $psCommand = "(Get-Printer -Name '$escapedName' -ErrorAction SilentlyContinue).PrinterStatus";
            $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $psCommand . '"';
            
            Log::info('Executing PowerShell command for printer status');

            $output = @shell_exec($cmd);
            
            Log::info('PowerShell printer status output: ' . var_export($output, true));
            
            if ($output === null || $output === false) {
                Log::warning('Printer status query returned null/false. Treating as offline to prevent printing.');
                return false; // Block printing if we can't check status
            }
            
            $statusRaw = trim($output);
            
            if ($statusRaw === '') {
                Log::warning('Printer status query returned empty. Treating as offline to prevent printing.');
                return false; // Block if status unknown
            }

            Log::info('Printer status value: ' . $statusRaw);

            // Check for problem statuses that should block printing
            $statusLower = strtolower($statusRaw);
            
            // Block on: NotAvailable (disconnected/out of paper), Error, Offline, Paused
            // Also check for compound statuses like "PaperOut, NotAvailable"
            $blockedKeywords = ['notavailable', 'paperout', 'error', 'offline', 'paused'];
            
            foreach ($blockedKeywords as $keyword) {
                if (strpos($statusLower, $keyword) !== false) {
                    Log::info('Printer status indicates problem (contains "' . $keyword . '"): ' . $statusRaw . ' - blocking printing');
                    return false;
                }
            }

            // Check 2: Look for stuck/error jobs in the queue
            $psJobCheck = "(Get-PrintJob -PrinterName '$escapedName' -ErrorAction SilentlyContinue | Where-Object { \$_.JobStatus -match 'Error|Paused|Blocked|Retained' }).Count";
            $cmdJobCheck = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $psJobCheck . '"';
            
            Log::info('Checking for error jobs in queue');
            
            $jobOutput = @shell_exec($cmdJobCheck);
            $errorJobCount = intval(trim($jobOutput ?? '0'));
            
            Log::info('Error job count: ' . $errorJobCount);
            
            if ($errorJobCount > 0) {
                Log::info('Found ' . $errorJobCount . ' stuck/error jobs in queue - blocking printing');
                return false;
            }

            // Accept: Normal, Idle, Printing
            Log::info('Printer status accepted as online: ' . $statusRaw);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Strict printer status check failed: ' . $e->getMessage() . ' — treating as offline.');
            return false;
        }
    }
}
