# Printing Setup: Linux Server to Windows 11 Client

This guide explains how to set up printing from your Laravel application running on Ubuntu Linux (`http://louna.lccdo.edu.ph`) to a receipt printer connected to a Windows 11 computer.

## Architecture

```
Ubuntu Server (Laravel)  →  Windows 11 Computer  →  EPSON TM-T82II Receipt Printer
  (louna.lccdo.edu.ph)         (Kiosk Station)         (USB/Network)
```

## Solution: HTTP Print API (Recommended)

The most reliable approach is to create a simple print service on the Windows 11 computer that receives print jobs via HTTP.

---

## Step 1: Install Print Service on Windows 11

### Option A: Using Node.js (Recommended)

1. **Install Node.js on Windows 11:**
   - Download from: https://nodejs.org/
   - Install the LTS version

2. **Create print server directory:**
   ```powershell
   mkdir C:\PrintServer
   cd C:\PrintServer
   ```

3. **Initialize Node.js project:**
   ```powershell
   npm init -y
   npm install express body-parser node-thermal-printer
   ```

4. **Create `print-server.js`:**
   ```javascript
   const express = require('express');
   const bodyParser = require('body-parser');
   const { ThermalPrinter, PrinterTypes } = require('node-thermal-printer');

   const app = express();
   app.use(bodyParser.json());
   app.use(bodyParser.text({ type: 'text/plain', limit: '10mb' }));

   // Configure your printer
   const printer = new ThermalPrinter({
       type: PrinterTypes.EPSON,
       interface: 'printer:EPSON TM-T82II Receipt', // Your printer name
       characterSet: 'PC437_USA',
       removeSpecialCharacters: false,
       lineCharacter: "=",
       breakLine: "\n",
   });

   app.post('/print', async (req, res) => {
       try {
           console.log('Received print job');
           
           const printData = req.body;
           
           // Clear any previous data
           printer.clear();
           
           // Print header/logo
           if (printData.logo) {
               printer.alignCenter();
               printer.println('LOURDES COLLEGE, INC.');
               printer.newLine();
           }
           
           // Print ticket number
           printer.alignCenter();
           printer.setTextSize(2, 2);
           printer.bold(true);
           printer.println(printData.code || 'TICKET');
           printer.bold(false);
           printer.setTextNormal();
           printer.newLine();
           
           // Print service type
           printer.alignLeft();
           printer.println('Service: ' + (printData.service || 'N/A'));
           printer.println('Priority: ' + (printData.priority || 'N/A'));
           printer.println('Time: ' + (printData.time || new Date().toLocaleString()));
           printer.newLine();
           
           // Print footer
           printer.alignCenter();
           printer.println('Please wait for your number');
           printer.println('to be called.');
           printer.newLine();
           printer.newLine();
           
           // Cut paper
           printer.cut();
           
           // Send to printer
           await printer.execute();
           
           console.log('Print job completed');
           res.json({ success: true, message: 'Printed successfully' });
           
       } catch (error) {
           console.error('Print error:', error);
           res.status(500).json({ success: false, error: error.message });
       }
   });

   // Health check endpoint
   app.get('/status', (req, res) => {
       res.json({ status: 'online', printer: 'EPSON TM-T82II Receipt' });
   });

   const PORT = 3000;
   app.listen(PORT, '0.0.0.0', () => {
       console.log(`Print server running on http://0.0.0.0:${PORT}`);
       console.log('Ready to receive print jobs from Laravel server');
   });
   ```

5. **Test the print server:**
   ```powershell
   node print-server.js
   ```

6. **Test from browser:**
   Open: `http://localhost:3000/status`

### Option B: Using Python (Alternative)

1. **Install Python 3:**
   - Download from: https://www.python.org/

2. **Create `print_server.py`:**
   ```python
   from flask import Flask, request, jsonify
   import win32print
   import win32ui
   from PIL import Image, ImageDraw, ImageFont
   import io

   app = Flask(__name__)

   PRINTER_NAME = "EPSON TM-T82II Receipt"

   @app.route('/print', methods=['POST'])
   def print_ticket():
       try:
           data = request.json
           code = data.get('code', 'TICKET')
           service = data.get('service', 'N/A')
           priority = data.get('priority', 'N/A')
           
           # Create print job
           hprinter = win32print.OpenPrinter(PRINTER_NAME)
           hdc = win32ui.CreateDC()
           hdc.CreatePrinterDC(PRINTER_NAME)
           
           hdc.StartDoc("Queue Ticket")
           hdc.StartPage()
           
           # Print content (simplified)
           hdc.TextOut(100, 100, f"Code: {code}")
           hdc.TextOut(100, 200, f"Service: {service}")
           hdc.TextOut(100, 300, f"Priority: {priority}")
           
           hdc.EndPage()
           hdc.EndDoc()
           
           win32print.ClosePrinter(hprinter)
           
           return jsonify({"success": True, "message": "Printed successfully"})
       
       except Exception as e:
           return jsonify({"success": False, "error": str(e)}), 500

   @app.route('/status', methods=['GET'])
   def status():
       return jsonify({"status": "online", "printer": PRINTER_NAME})

   if __name__ == '__main__':
       app.run(host='0.0.0.0', port=3000)
   ```

3. **Install dependencies:**
   ```powershell
   pip install flask pywin32 Pillow
   ```

4. **Run:**
   ```powershell
   python print_server.py
   ```

---

## Step 2: Run Print Server as Windows Service

To ensure the print server starts automatically with Windows:

### Using NSSM (Non-Sucking Service Manager)

1. **Download NSSM:**
   - Download from: https://nssm.cc/download

2. **Install as service:**
   ```powershell
   nssm install PrintServer "C:\Program Files\nodejs\node.exe" "C:\PrintServer\print-server.js"
   nssm set PrintServer AppDirectory C:\PrintServer
   nssm set PrintServer DisplayName "Queue System Print Server"
   nssm set PrintServer Description "Receives print jobs from Laravel queueing system"
   nssm set PrintServer Start SERVICE_AUTO_START
   nssm start PrintServer
   ```

3. **Check service status:**
   ```powershell
   nssm status PrintServer
   ```

---

## Step 3: Configure Windows Firewall

Allow incoming connections on port 3000:

```powershell
New-NetFirewallRule -DisplayName "Print Server" -Direction Inbound -Protocol TCP -LocalPort 3000 -Action Allow
```

Or use Windows Firewall GUI:
1. Open Windows Defender Firewall
2. Advanced Settings
3. Inbound Rules → New Rule
4. Port → TCP → 3000 → Allow the connection

---

## Step 4: Update Laravel Application on Ubuntu Server

### Update `.env` file:

```ini
PRINTER_ENABLED=true
PRINTER_TYPE=http
PRINTER_TARGET=http://192.168.1.100:3000/print
```

Replace `192.168.1.100` with your Windows 11 computer's IP address.

### Update `KioskController.php`:

Add this method to handle HTTP printing:

```php
protected function printTicket(QueueTicket $ticket)
{
    try {
        Log::info('Starting print job for ticket: ' . $ticket->code);
        
        $printerType = config('app.printer_type', 'windows');
        
        if ($printerType === 'http') {
            return $this->printViaHttp($ticket);
        }
        
        // Original ESC/POS printing logic for local Windows
        // ... existing code ...
        
    } catch (\Throwable $e) {
        Log::error('Print failed: ' . $e->getMessage());
    }
}

protected function printViaHttp(QueueTicket $ticket)
{
    try {
        $printerUrl = config('app.printer_target');
        
        if (!$printerUrl) {
            Log::error('PRINTER_TARGET not configured in .env');
            return;
        }
        
        // Prepare print data
        $printData = [
            'code' => $ticket->code,
            'service' => ucfirst($ticket->service_type),
            'priority' => ucfirst(str_replace('_', ' ', $ticket->priority)),
            'time' => $ticket->created_at->format('Y-m-d H:i:s'),
            'logo' => true,
        ];
        
        // Send HTTP request to Windows print server
        $response = \Illuminate\Support\Facades\Http::timeout(5)
            ->post($printerUrl, $printData);
        
        if ($response->successful()) {
            Log::info('Print job sent successfully via HTTP');
        } else {
            Log::error('Print server returned error: ' . $response->body());
        }
        
    } catch (\Throwable $e) {
        Log::error('HTTP print failed: ' . $e->getMessage());
    }
}
```

### Update `config/app.php`:

Add printer configuration:

```php
'printer_enabled' => env('PRINTER_ENABLED', false),
'printer_type' => env('PRINTER_TYPE', 'windows'),
'printer_target' => env('PRINTER_TARGET', ''),
'printer_port' => env('PRINTER_PORT', 9100),
```

---

## Step 5: Test the Setup

### From Ubuntu Server:

```bash
curl -X POST http://192.168.1.100:3000/print \
  -H "Content-Type: application/json" \
  -d '{
    "code": "CS-001",
    "service": "Cashier",
    "priority": "Student",
    "time": "2025-12-09 10:30:00"
  }'
```

Expected response:
```json
{"success": true, "message": "Printed successfully"}
```

### From Laravel:

Access the kiosk and generate a ticket. Check the Laravel logs:

```bash
tail -f /var/www/html/queueing_system/storage/logs/laravel.log
```

---

## Troubleshooting

### Print server not accessible from Ubuntu:

1. **Check Windows IP:**
   ```powershell
   ipconfig
   ```

2. **Test connectivity:**
   ```bash
   ping 192.168.1.100
   telnet 192.168.1.100 3000
   ```

3. **Check firewall:**
   ```powershell
   Get-NetFirewallRule | Where-Object {$_.DisplayName -like "*Print*"}
   ```

### Printer not found:

1. **List available printers:**
   ```powershell
   Get-Printer | Format-Table Name, DriverName
   ```

2. **Update printer name in `print-server.js`:**
   ```javascript
   interface: 'printer:YOUR_EXACT_PRINTER_NAME'
   ```

### Print server crashes:

1. **Check logs:**
   ```powershell
   Get-EventLog -LogName Application -Source "Print Server" -Newest 10
   ```

2. **Restart service:**
   ```powershell
   nssm restart PrintServer
   ```

### Laravel not sending print jobs:

1. **Check Laravel logs:**
   ```bash
   tail -f /var/www/html/queueing_system/storage/logs/laravel.log
   ```

2. **Test HTTP client:**
   ```bash
   php artisan tinker
   >>> \Illuminate\Support\Facades\Http::get('http://192.168.1.100:3000/status')
   ```

---

## Network Configuration Tips

### Static IP for Windows 11 Computer:

Set a static IP to avoid changes after DHCP lease renewal:

1. Open Settings → Network & Internet → Ethernet/WiFi
2. Click on your connection
3. IP assignment → Edit → Manual
4. Set static IP (e.g., 192.168.1.100)
5. Update `.env` on Ubuntu server

### DNS Resolution (Optional):

Instead of using IP addresses, configure local DNS:

1. **Add to Ubuntu's `/etc/hosts`:**
   ```
   192.168.1.100  print-server.local
   ```

2. **Update `.env`:**
   ```ini
   PRINTER_TARGET=http://print-server.local:3000/print
   ```

---

## Security Considerations

1. **Use HTTPS (Recommended for production):**
   - Install SSL certificate on print server
   - Use reverse proxy (nginx) on Windows

2. **Add authentication:**
   - Implement API key validation
   - Use VPN for printer network

3. **Restrict access:**
   - Configure firewall to only allow Laravel server IP
   - Use private VLAN for kiosk/printer network

---

## Alternative: Direct Network Printing

If your EPSON TM-T82II has network capability:

1. **Connect printer to network via Ethernet**

2. **Find printer IP:**
   - Print network configuration from printer
   - Or check router DHCP leases

3. **Update `.env`:**
   ```ini
   PRINTER_ENABLED=true
   PRINTER_TYPE=network
   PRINTER_TARGET=192.168.1.50
   PRINTER_PORT=9100
   ```

4. **Use raw socket printing in PHP:**
   ```php
   $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
   socket_connect($socket, '192.168.1.50', 9100);
   socket_write($socket, $escposCommands);
   socket_close($socket);
   ```

This eliminates the need for the Windows computer entirely.

---

## Summary

**Recommended Setup:**
- HTTP Print API on Windows 11 (most flexible and reliable)
- NSSM service for automatic startup
- Static IP for Windows computer
- Firewall rule for port 3000

This approach provides:
✅ Reliable network printing  
✅ Easy troubleshooting  
✅ Works across different OSes  
✅ Centralized print management  
✅ Can support multiple printers  

For support or issues, check the print server logs and Laravel logs as shown in the troubleshooting section.
