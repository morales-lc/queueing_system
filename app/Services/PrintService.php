<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrintService
{
    protected string $printServerUrl;
    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = config('app.printer_enabled', false);
        $this->printServerUrl = config('app.print_server_url', 'http://192.168.0.95:3000');
    }

    /**
     * Check if printing is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check print server health
     */
    public function checkHealth(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $response = Http::timeout(5)->get("{$this->printServerUrl}/health");
            return $response->successful() && $response->json('status') === 'ok';
        } catch (\Exception $e) {
            Log::error('Print server health check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check printer status
     */
    public function checkPrinterStatus(): array
    {
        if (!$this->enabled) {
            return ['online' => false, 'message' => 'Printing disabled'];
        }

        try {
            $response = Http::timeout(5)->get("{$this->printServerUrl}/printer/status");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return ['online' => false, 'message' => 'Failed to get printer status'];
        } catch (\Exception $e) {
            Log::error('Printer status check failed: ' . $e->getMessage());
            return ['online' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send raw ESC/POS data to print server
     * 
     * @param string $rawData Raw ESC/POS binary data
     * @return bool Success status
     */
    public function printRaw(string $rawData): bool
    {
        if (!$this->enabled) {
            Log::info('Printing disabled - skipping print job');
            return true; // Return true to not block the workflow
        }

        try {
            // Encode data as base64
            $base64Data = base64_encode($rawData);

            // Send to print server
            $response = Http::timeout(10)
                ->post("{$this->printServerUrl}/print", [
                    'data' => $base64Data
                ]);

            if ($response->successful()) {
                Log::info('Print job sent successfully');
                return true;
            }

            Log::error('Print job failed: ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('Print service error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build and send a receipt ticket
     * 
     * @param object $ticket QueueTicket model
     * @return bool Success status
     */
    public function printTicket($ticket): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            // Build ESC/POS commands
            $data = $this->buildTicketData($ticket);
            
            return $this->printRaw($data);

        } catch (\Exception $e) {
            Log::error('Ticket printing failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build ESC/POS commands for a ticket
     */
    protected function buildTicketData($ticket): string
    {
        $esc = "\x1B";
        $gs = "\x1D";
        
        $data = '';
        
        // Initialize printer
        $data .= $esc . "@";
        
        // Set center alignment
        $data .= $esc . "a" . chr(1);
        
        // Bold text
        $data .= $esc . "E" . chr(1);
        
        // Print header
        $data .= "LOURDES COLLEGE, INC.\n";
        $data .= $esc . "E" . chr(0); // Turn off bold
        $data .= "Cagayan de Oro City\n\n";
        
        // Bold on for title
        $data .= $esc . "E" . chr(1);
        $data .= "QUEUE TICKET\n";
        $data .= $esc . "E" . chr(0);
        $data .= "\n";
        
        // Separator
        $data .= "--------------------------------\n\n";
        
        // Large text for queue number
        $data .= $gs . "!" . chr(0x11); // Double height and width
        $data .= "NUMBER\n";
        $data .= $ticket->code . "\n\n";
        $data .= $gs . "!" . chr(0); // Normal size
        
        // Separator
        $data .= "--------------------------------\n\n";
        
        // Service type
        $serviceType = strtoupper($ticket->service_type);
        $data .= "Service: " . $serviceType . "\n";
        
        // Priority label
        $priorityLabel = match($ticket->priority) {
            'pwd_senior_pregnant' => 'PWD/Senior/Pregnant',
            'student' => 'Student',
            'parent' => 'Parent/Guardian',
            default => ucfirst($ticket->priority)
        };
        $data .= "Priority: " . $priorityLabel . "\n\n";
        
        // Date and time
        $data .= "Date: " . now()->format('M d, Y') . "\n";
        $data .= "Time: " . now()->format('h:i A') . "\n\n";
        
        // Instructions
        $data .= "Please wait for your number\n";
        $data .= "to be called.\n\n";
        $data .= "Thank you!\n\n";
        
        // Cut paper
        $data .= $gs . "V" . chr(66) . chr(0);
        
        return $data;
    }
}
