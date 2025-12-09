# Receipt Print Server - Quick Setup Guide

## For Windows 11 Computer (Where the Printer is Connected)

### Step 1: Install Node.js
1. Download Node.js LTS from: https://nodejs.org/
2. Run the installer and follow the prompts
3. Verify installation:
   ```powershell
   node --version
   npm --version
   ```

### Step 2: Copy Print Server Files
Copy the `print-server` folder to your Windows 11 computer:
- `print-server.js`
- `package.json`

Example location: `C:\PrintServer\`

### Step 3: Install Dependencies
Open PowerShell as Administrator and run:

```powershell
cd C:\PrintServer
npm install
```

This will install:
- express (web server)
- body-parser (JSON parsing)
- node-thermal-printer (receipt printing)
- cors (cross-origin requests)

### Step 4: Update Printer Name (if needed)
Open `print-server.js` and update line 26 if your printer has a different name:

```javascript
const PRINTER_NAME = 'EPSON TM-T82II Receipt'; // Change this if needed
```

To find your printer name:
```powershell
Get-Printer | Format-Table Name, DriverName
```

### Step 5: Test the Print Server

```powershell
node print-server.js
```

You should see:
```
╔════════════════════════════════════════════════════════╗
║      Receipt Print Server - Windows 11                 ║
╚════════════════════════════════════════════════════════╝

✓ Server running on: http://0.0.0.0:3000
✓ Printer: EPSON TM-T82II Receipt
✓ Ready to receive print jobs from Laravel
```

### Step 6: Test Printing
Open a browser and go to:
```
http://localhost:3000/test
```

This should print a test ticket. If it works, proceed to Step 7.

### Step 7: Configure Firewall
Allow incoming connections on port 3000:

```powershell
New-NetFirewallRule -DisplayName "Print Server" -Direction Inbound -Protocol TCP -LocalPort 3000 -Action Allow
```

### Step 8: Find Your Windows IP Address

```powershell
ipconfig
```

Look for "IPv4 Address" (e.g., 192.168.1.100)

### Step 9: Install as Windows Service (NSSM)

1. **Download NSSM:**
   - Go to: https://nssm.cc/download
   - Download nssm-2.24.zip
   - Extract to `C:\nssm\`

2. **Install service:**
   ```powershell
   cd C:\nssm\win64
   
   .\nssm.exe install PrintServer "C:\Program Files\nodejs\node.exe" "C:\PrintServer\print-server.js"
   .\nssm.exe set PrintServer AppDirectory "C:\PrintServer"
   .\nssm.exe set PrintServer DisplayName "Queue System Print Server"
   .\nssm.exe set PrintServer Description "Receives print jobs from Laravel queueing system"
   .\nssm.exe set PrintServer Start SERVICE_AUTO_START
   .\nssm.exe start PrintServer
   ```

3. **Verify service:**
   ```powershell
   .\nssm.exe status PrintServer
   ```

---

## For Ubuntu Server (Where Laravel is Running)

### Step 1: Update .env File

SSH into your Ubuntu server:
```bash
ssh user@louna.lccdo.edu.ph
```

Edit the `.env` file:
```bash
cd /var/www/html/queueing_system
sudo nano .env
```

Update these lines (replace `192.168.1.100` with your Windows IP):
```ini
PRINTER_ENABLED=true
PRINTER_TYPE=http
PRINTER_TARGET=http://192.168.1.100:3000/print
```

### Step 2: Clear Laravel Cache

```bash
sudo php artisan config:clear
sudo php artisan cache:clear
```

### Step 3: Test from Ubuntu

```bash
curl -X POST http://192.168.1.100:3000/print \
  -H "Content-Type: application/json" \
  -d '{
    "code": "CS-999",
    "service": "Cashier",
    "priority": "Student",
    "time": "Dec. 9, 2025 10:30 AM",
    "logo": true
  }'
```

Expected response:
```json
{"success":true,"message":"Printed successfully","ticket":"CS-999"}
```

### Step 4: Test from Kiosk

1. Open browser: `http://louna.lccdo.edu.ph`
2. Generate a ticket
3. Check Laravel logs:
   ```bash
   tail -f /var/www/html/queueing_system/storage/logs/laravel.log
   ```

You should see:
```
[timestamp] Starting print job for ticket: CS-001
[timestamp] Sending print job via HTTP to: http://192.168.1.100:3000/print
[timestamp] Print job sent successfully via HTTP
```

---

## Troubleshooting

### Cannot connect to print server
```bash
# Test connectivity from Ubuntu
ping 192.168.1.100
telnet 192.168.1.100 3000
```

If telnet fails, check Windows firewall.

### Printer not found
```powershell
# On Windows, list printers
Get-Printer | Format-Table Name, DriverName

# Update printer name in print-server.js
```

### Service won't start
```powershell
# Check service status
nssm status PrintServer

# View service logs
Get-EventLog -LogName Application -Source "PrintServer" -Newest 10

# Restart service
nssm restart PrintServer
```

### Laravel not sending print jobs
```bash
# Check Laravel logs
tail -f /var/www/html/queueing_system/storage/logs/laravel.log

# Test HTTP client
php artisan tinker
>>> \Illuminate\Support\Facades\Http::get('http://192.168.1.100:3000/status')
```

---

## Management Commands

### Start/Stop Service (Windows)
```powershell
nssm start PrintServer
nssm stop PrintServer
nssm restart PrintServer
nssm status PrintServer
```

### View Service Logs (Windows)
```powershell
Get-EventLog -LogName Application -Source "PrintServer" -Newest 20
```

### Remove Service (Windows)
```powershell
nssm stop PrintServer
nssm remove PrintServer confirm
```

---

## Configuration Reference

### Print Server Endpoints

- **GET /**  
  Web interface showing server status

- **GET /status**  
  JSON status response

- **GET /test**  
  Print a test ticket

- **POST /print**  
  Print a queue ticket (JSON body)

### Laravel .env Options

```ini
# Enable/disable printing
PRINTER_ENABLED=true

# Printing method: windows, network, or http
PRINTER_TYPE=http

# For http: Full URL to print endpoint
PRINTER_TARGET=http://192.168.1.100:3000/print

# For network: IP address (not used for http)
# PRINTER_TARGET=192.168.1.50
# PRINTER_PORT=9100
```

---

## Security Notes

1. **Static IP**: Set a static IP for the Windows computer to avoid address changes
2. **Firewall**: Only allow connections from the Laravel server IP
3. **Network**: Use a private VLAN for kiosk/printer network
4. **Authentication**: Consider adding API key authentication for production

---

## Success Checklist

- [ ] Node.js installed on Windows 11
- [ ] Print server dependencies installed (`npm install`)
- [ ] Printer name configured correctly
- [ ] Test print works (`/test` endpoint)
- [ ] Firewall rule added (port 3000)
- [ ] Service installed with NSSM
- [ ] Service starts automatically
- [ ] Laravel `.env` updated with correct IP
- [ ] Laravel cache cleared
- [ ] Test print from Ubuntu successful
- [ ] Kiosk generates and prints tickets

---

For detailed information, see `PRINTING_SETUP.md`
