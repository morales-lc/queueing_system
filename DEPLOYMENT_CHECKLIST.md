# HTTP Print Server - Deployment Checklist

Use this checklist when setting up printing from Linux Ubuntu to Windows 11.

## Pre-Deployment Checklist

### Prerequisites
- [ ] Ubuntu Server running with Laravel application deployed
- [ ] Windows 11 computer available at kiosk station
- [ ] EPSON TM-T82II Receipt printer connected to Windows 11 (USB or Network)
- [ ] Both computers on same network
- [ ] Printer installed and working on Windows 11

---

## Windows 11 Setup (Kiosk Computer)

### 1. Install Node.js
- [ ] Download Node.js LTS from https://nodejs.org/
- [ ] Run installer (use default settings)
- [ ] Verify installation:
  ```powershell
  node --version
  npm --version
  ```

### 2. Copy Print Server Files
- [ ] Create folder: `C:\PrintServer\`
- [ ] Copy `print-server` folder contents to `C:\PrintServer\`
- [ ] Verify files exist:
  - [ ] `print-server.js`
  - [ ] `package.json`
  - [ ] `README.md`
  - [ ] `start.bat`

### 3. Configure Printer Name
- [ ] Open PowerShell and list printers:
  ```powershell
  Get-Printer | Format-Table Name, DriverName
  ```
- [ ] Copy exact printer name (e.g., "EPSON TM-T82II Receipt")
- [ ] Open `C:\PrintServer\print-server.js` in Notepad
- [ ] Update line 26 with correct printer name
- [ ] Save file

### 4. Install Dependencies
- [ ] Open PowerShell as Administrator
- [ ] Navigate to print server directory:
  ```powershell
  cd C:\PrintServer
  ```
- [ ] Install packages:
  ```powershell
  npm install
  ```
- [ ] Wait for completion (may take 2-3 minutes)
- [ ] Verify `node_modules` folder was created

### 5. Test Print Server
- [ ] Start print server:
  ```powershell
  node print-server.js
  ```
  OR double-click `start.bat`
- [ ] Verify console shows:
  ```
  ✓ Server running on: http://0.0.0.0:3000
  ✓ Printer: EPSON TM-T82II Receipt
  ✓ Ready to receive print jobs
  ```
- [ ] Open browser: `http://localhost:3000`
- [ ] Click "Print Test Ticket" button
- [ ] Verify test ticket prints successfully
- [ ] Press Ctrl+C to stop server

### 6. Get Windows IP Address
- [ ] Open PowerShell:
  ```powershell
  ipconfig
  ```
- [ ] Find "IPv4 Address" under your network adapter
- [ ] Write down IP address: `___.___.___.___`
- [ ] Ping test from same computer:
  ```powershell
  ping [YOUR_IP]
  ```

### 7. Configure Firewall
- [ ] Open PowerShell as Administrator
- [ ] Add firewall rule:
  ```powershell
  New-NetFirewallRule -DisplayName "Print Server" -Direction Inbound -Protocol TCP -LocalPort 3000 -Action Allow
  ```
- [ ] Verify rule was created:
  ```powershell
  Get-NetFirewallRule -DisplayName "Print Server"
  ```

### 8. Install as Windows Service
- [ ] Download NSSM from https://nssm.cc/download
- [ ] Extract to `C:\nssm\`
- [ ] Open PowerShell as Administrator:
  ```powershell
  cd C:\nssm\win64
  .\nssm.exe install PrintServer "C:\Program Files\nodejs\node.exe" "C:\PrintServer\print-server.js"
  .\nssm.exe set PrintServer AppDirectory "C:\PrintServer"
  .\nssm.exe set PrintServer DisplayName "Queue System Print Server"
  .\nssm.exe set PrintServer Start SERVICE_AUTO_START
  .\nssm.exe start PrintServer
  ```
- [ ] Verify service is running:
  ```powershell
  .\nssm.exe status PrintServer
  ```
- [ ] Test from browser: `http://localhost:3000`

### 9. Set Static IP (Recommended)
- [ ] Open Settings → Network & Internet
- [ ] Click your connection (Ethernet/WiFi)
- [ ] IP assignment → Edit → Manual
- [ ] Enable IPv4
- [ ] Enter IP address (e.g., 192.168.1.100)
- [ ] Enter Subnet mask (e.g., 255.255.255.0)
- [ ] Enter Gateway (e.g., 192.168.1.1)
- [ ] Save changes

---

## Ubuntu Server Setup (Laravel Application)

### 1. SSH to Server
- [ ] Connect to server:
  ```bash
  ssh user@louna.lccdo.edu.ph
  ```

### 2. Test Connectivity to Windows
- [ ] Ping Windows computer:
  ```bash
  ping [WINDOWS_IP]
  ```
- [ ] Test HTTP connection:
  ```bash
  curl http://[WINDOWS_IP]:3000/status
  ```
  Should return JSON with status "online"

### 3. Update .env File
- [ ] Navigate to Laravel directory:
  ```bash
  cd /var/www/html/queueing_system
  ```
- [ ] Backup current .env:
  ```bash
  sudo cp .env .env.backup
  ```
- [ ] Edit .env file:
  ```bash
  sudo nano .env
  ```
- [ ] Update printer settings (replace [WINDOWS_IP] with actual IP):
  ```ini
  PRINTER_ENABLED=true
  PRINTER_TYPE=http
  PRINTER_TARGET=http://[WINDOWS_IP]:3000/print
  ```
- [ ] Save file (Ctrl+X, Y, Enter)

### 4. Clear Laravel Cache
- [ ] Clear configuration cache:
  ```bash
  sudo php artisan config:clear
  ```
- [ ] Clear application cache:
  ```bash
  sudo php artisan cache:clear
  ```
- [ ] Clear route cache:
  ```bash
  sudo php artisan route:clear
  ```

### 5. Test Print from Ubuntu
- [ ] Test with curl:
  ```bash
  curl -X POST http://[WINDOWS_IP]:3000/print \
    -H "Content-Type: application/json" \
    -d '{"code":"TEST-999","service":"Cashier","priority":"Student","time":"Dec. 9, 2025 10:30 AM","logo":true}'
  ```
- [ ] Verify ticket prints on Windows printer
- [ ] Check response is success:
  ```json
  {"success":true,"message":"Printed successfully","ticket":"TEST-999"}
  ```

---

## Final Testing

### 1. Test Kiosk Application
- [ ] Open kiosk in browser: `http://louna.lccdo.edu.ph`
- [ ] Select service (Cashier or Registrar)
- [ ] Select priority
- [ ] Click to generate ticket
- [ ] Verify ticket prints on Windows printer
- [ ] Verify ticket displays on screen

### 2. Monitor Logs

**On Ubuntu Server:**
- [ ] Open Laravel logs:
  ```bash
  tail -f /var/www/html/queueing_system/storage/logs/laravel.log
  ```
- [ ] Generate a ticket from kiosk
- [ ] Verify log shows:
  ```
  Starting print job for ticket: [CODE]
  Sending print job via HTTP to: http://[WINDOWS_IP]:3000/print
  Print job sent successfully via HTTP
  ```

**On Windows 11:**
- [ ] If running print server in console, check output
- [ ] If running as service, check Event Viewer:
  ```powershell
  Get-EventLog -LogName Application -Source "PrintServer" -Newest 10
  ```

### 3. Test Multiple Tickets
- [ ] Generate 5 tickets in quick succession
- [ ] Verify all print correctly
- [ ] Verify no tickets are lost or duplicated

### 4. Test Error Handling
- [ ] Stop print server on Windows
- [ ] Generate ticket on kiosk
- [ ] Verify Laravel logs show connection error (not crash)
- [ ] Start print server again
- [ ] Verify printing resumes

---

## Post-Deployment

### Documentation
- [ ] Document Windows IP address
- [ ] Document printer name used
- [ ] Save backup of configuration files
- [ ] Update network diagram

### Training
- [ ] Show staff how to check if print server is running
- [ ] Show staff how to restart print server service
- [ ] Provide troubleshooting guide

### Monitoring
- [ ] Set up monitoring for print server uptime
- [ ] Configure alerts for service failures
- [ ] Schedule regular printer maintenance

---

## Troubleshooting Reference

### Print server not accessible from Ubuntu
```bash
# Test connectivity
ping [WINDOWS_IP]
telnet [WINDOWS_IP] 3000

# Check firewall
# On Windows:
Get-NetFirewallRule -DisplayName "Print Server"
```

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

# View logs
Get-EventLog -LogName Application -Source "PrintServer" -Newest 10

# Restart service
nssm restart PrintServer
```

### Laravel not sending print jobs
```bash
# Check configuration
php artisan config:show app.printer_type
php artisan config:show app.printer_target

# Test HTTP client
php artisan tinker
>>> \Illuminate\Support\Facades\Http::get('http://[WINDOWS_IP]:3000/status')
```

---

## Success Criteria

All items must be checked before considering deployment complete:

- [ ] ✅ Print server running as Windows service
- [ ] ✅ Service starts automatically with Windows
- [ ] ✅ Test ticket prints from browser
- [ ] ✅ Ubuntu can connect to print server
- [ ] ✅ Laravel .env configured correctly
- [ ] ✅ Kiosk generates and prints tickets
- [ ] ✅ Multiple tickets print without issues
- [ ] ✅ Error handling works (server offline scenario)
- [ ] ✅ Logs are accessible and readable
- [ ] ✅ Staff trained on basic troubleshooting

---

**Date Completed:** _______________

**Completed By:** _______________

**Windows IP:** _______________

**Notes:**
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________
