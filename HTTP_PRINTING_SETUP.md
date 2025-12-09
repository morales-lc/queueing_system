# HTTP Print Server Setup - Quick Reference

## Overview

Your Laravel system is now configured to print from Linux Ubuntu to Windows 11 using HTTP:

```
Ubuntu Server (Laravel)  →  HTTP Request  →  Windows 11 Print Server  →  EPSON Printer
louna.lccdo.edu.ph              Port 3000         (Node.js Service)        (USB/Network)
```

## What's Been Updated

### ✅ Laravel Application (Ubuntu Server)

**Files Modified:**
- `app/Http/Controllers/KioskController.php` - Added HTTP printing support
- `.env` - Configured for HTTP printing

**New Configuration:**
```ini
PRINTER_ENABLED=true
PRINTER_TYPE=http
PRINTER_TARGET=http://192.168.1.100:3000/print
```

### ✅ Print Server (Windows 11)

**New Files Created:**
- `print-server/print-server.js` - Node.js HTTP print server
- `print-server/package.json` - Dependencies configuration
- `print-server/README.md` - Detailed setup guide
- `print-server/start.bat` - Quick start script
- `print-server/.env.example` - Configuration template

## Next Steps

### On Windows 11 Computer (Kiosk Station)

1. **Copy the `print-server` folder to Windows 11**
   - Location: `C:\PrintServer\`

2. **Install Node.js**
   ```
   Download from: https://nodejs.org/
   Install LTS version
   ```

3. **Open PowerShell as Administrator and run:**
   ```powershell
   cd C:\PrintServer
   npm install
   ```

4. **Test the print server:**
   ```powershell
   node print-server.js
   ```
   or simply double-click `start.bat`

5. **Open browser and test:**
   ```
   http://localhost:3000/test
   ```
   This should print a test ticket.

6. **Get Windows IP address:**
   ```powershell
   ipconfig
   ```
   Note the IPv4 Address (e.g., 192.168.1.100)

7. **Configure Firewall:**
   ```powershell
   New-NetFirewallRule -DisplayName "Print Server" -Direction Inbound -Protocol TCP -LocalPort 3000 -Action Allow
   ```

8. **Install as Windows Service (Optional but Recommended):**
   - Download NSSM: https://nssm.cc/download
   - Install service (see `print-server/README.md` for details)

### On Ubuntu Server

1. **SSH to server:**
   ```bash
   ssh user@louna.lccdo.edu.ph
   ```

2. **Update `.env` with Windows IP:**
   ```bash
   cd /var/www/html/queueing_system
   sudo nano .env
   ```
   
   Change:
   ```ini
   PRINTER_TARGET=http://192.168.1.100:3000/print
   ```
   (Replace with actual Windows IP)

3. **Clear cache:**
   ```bash
   sudo php artisan config:clear
   sudo php artisan cache:clear
   ```

4. **Test connectivity:**
   ```bash
   curl http://192.168.1.100:3000/status
   ```

5. **Test printing:**
   ```bash
   curl -X POST http://192.168.1.100:3000/print \
     -H "Content-Type: application/json" \
     -d '{"code":"TEST-001","service":"Cashier","priority":"Student","time":"Dec. 9, 2025 10:30 AM","logo":true}'
   ```

## Testing

### 1. Test Print Server Directly (on Windows)
```
http://localhost:3000/test
```

### 2. Test from Ubuntu Server
```bash
curl http://192.168.1.100:3000/status
```

### 3. Test from Kiosk
```
http://louna.lccdo.edu.ph
```
Generate a ticket and check if it prints.

## Monitoring

### Windows Print Server Logs
The server outputs logs to console. If running as service:
```powershell
Get-EventLog -LogName Application -Source "PrintServer" -Newest 10
```

### Laravel Logs (Ubuntu)
```bash
tail -f /var/www/html/queueing_system/storage/logs/laravel.log
```

Look for:
```
Starting print job for ticket: CS-001
Sending print job via HTTP to: http://192.168.1.100:3000/print
Print job sent successfully via HTTP
```

## Troubleshooting

### Problem: Cannot connect to print server from Ubuntu

**Solution:**
1. Check Windows IP address is correct
2. Verify firewall rule is active:
   ```powershell
   Get-NetFirewallRule -DisplayName "Print Server"
   ```
3. Test connectivity:
   ```bash
   ping 192.168.1.100
   telnet 192.168.1.100 3000
   ```

### Problem: Printer not found on Windows

**Solution:**
1. List available printers:
   ```powershell
   Get-Printer | Format-Table Name, DriverName
   ```
2. Update printer name in `print-server.js` line 26

### Problem: Print server crashes

**Solution:**
1. Check Node.js version: `node --version` (should be 18.x or higher)
2. Reinstall dependencies:
   ```powershell
   rm -rf node_modules
   npm install
   ```
3. Check printer is powered on and connected

### Problem: Tickets generated but not printing

**Solution:**
1. Check Laravel logs for HTTP errors
2. Verify PRINTER_TYPE=http in Ubuntu .env
3. Test print server directly: `http://WINDOWS_IP:3000/test`
4. Check Windows print server is running

## Advantages of This Setup

✅ **Works across different operating systems** (Linux → Windows)
✅ **Easy to troubleshoot** (HTTP requests, clear logs)
✅ **Reliable** (No SMB/CIFS complexity)
✅ **Flexible** (Can add multiple printers easily)
✅ **Centralized** (Manage printing from Windows computer)
✅ **Testable** (Web interface for testing)
✅ **Resilient** (Fails gracefully if printer is offline)

## Port Reference

- **Port 3000**: Print Server HTTP API (Windows 11)
- **Port 8080**: Laravel Reverb WebSocket (Ubuntu)
- **Port 80**: Apache Web Server (Ubuntu)

## Files to Commit to GitHub

```
✅ app/Http/Controllers/KioskController.php (updated)
✅ .env.example (updated with http config)
✅ print-server/print-server.js (new)
✅ print-server/package.json (new)
✅ print-server/README.md (new)
✅ print-server/start.bat (new)
✅ print-server/.env.example (new)
✅ PRINTING_SETUP.md (existing)
✅ DEPLOYMENT.md (existing)
❌ .env (do not commit - contains actual IPs)
```

## Documentation

- **Complete Setup Guide**: `PRINTING_SETUP.md`
- **Print Server Guide**: `print-server/README.md`
- **Deployment Guide**: `DEPLOYMENT.md`
- **This Summary**: `HTTP_PRINTING_SETUP.md`

---

**Status:** Ready to deploy! Follow the "Next Steps" section above.

**Support:** Check logs on both Windows and Ubuntu if issues occur.
