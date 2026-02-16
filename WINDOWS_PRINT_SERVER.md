# Windows Print Server Setup Guide

This guide explains how to set up the Windows 11 computer (IP: 192.168.138.20) as a print server for the Linux Ubuntu server.

## Overview

The architecture is:
- **Linux Server** (192.168.138.30) - Runs Laravel app with LAMP stack
- **Windows Client** (192.168.138.20) - Connected to EPSON TM-T82II Receipt printer
- Communication via HTTP API

## Step 1: Install Node.js on Windows 11

1. Download Node.js LTS from https://nodejs.org/
2. Run the installer and follow the prompts
3. Verify installation:
   ```cmd
   node --version
   npm --version
   ```

## Step 2: Create Print Server Directory

```cmd
cd C:\
mkdir PrintServer
cd PrintServer
```

## Step 3: Copy Print Server Files

Copy the `print-server.js` file from your project to `C:\PrintServer\`

## Step 4: Initialize Node.js Project

```cmd
cd C:\PrintServer
npm init -y
```

## Step 5: Install Dependencies

```cmd
npm install express body-parser printer
```

## Step 6: Verify Printer Name

Open PowerShell and check your printer name:

```powershell
Get-Printer | Select-Object Name
```

If the printer name is different from "EPSON TM-T82II Receipt", edit `print-server.js` and update the `PRINTER_NAME` constant.

## Step 7: Configure Windows Firewall

Allow incoming connections on port 3000:

```powershell
# Run PowerShell as Administrator
New-NetFirewallRule -DisplayName "Print Server Port 3000" -Direction Inbound -LocalPort 3000 -Protocol TCP -Action Allow
```

Or manually:
1. Open Windows Defender Firewall
2. Click "Advanced settings"
3. Click "Inbound Rules" → "New Rule"
4. Select "Port" → Next
5. Select "TCP" and enter "3000" → Next
6. Select "Allow the connection" → Next
7. Select all profiles → Next
8. Name it "Print Server Port 3000" → Finish

## Step 8: Test the Print Server

1. Start the server:
   ```cmd
   cd C:\PrintServer
   node print-server.js
   ```

2. You should see:
   ```
   ==================================================
   Windows Print Server Started
   ==================================================
   Server running on: http://0.0.0.0:3000
   Printer: EPSON TM-T82II Receipt
   Access from network: http://192.168.138.20:3000
   ```

3. Test from Windows browser:
   - Open browser and go to: http://localhost:3000/health
   - You should see: `{"status":"online","printer":"EPSON TM-T82II Receipt","timestamp":"..."}`

4. Test from Linux server:
   ```bash
   curl http://192.168.138.20:3000/health
   ```

## Step 9: Set Up as Windows Service (Auto-start)

To keep the print server running even after restart:

### Option A: Using NSSM (Non-Sucking Service Manager)

1. Download NSSM from https://nssm.cc/download
2. Extract to `C:\nssm\`
3. Open PowerShell as Administrator:

```powershell
cd C:\nssm\win64

# Install service
.\nssm.exe install PrintServer "C:\Program Files\nodejs\node.exe" "C:\PrintServer\print-server.js"

# Configure service
.\nssm.exe set PrintServer AppDirectory "C:\PrintServer"
.\nssm.exe set PrintServer DisplayName "Queue System Print Server"
.\nssm.exe set PrintServer Description "HTTP Print Server for Queue Management System"
.\nssm.exe set PrintServer Start SERVICE_AUTO_START

# Start service
.\nssm.exe start PrintServer
```

4. Verify service is running:
```powershell
Get-Service PrintServer
```

### Option B: Using PM2

1. Install PM2 globally:
```cmd
npm install -g pm2
npm install -g pm2-windows-startup
```

2. Configure PM2 to start on boot:
```cmd
pm2-startup install
```

3. Start the print server with PM2:
```cmd
cd C:\PrintServer
pm2 start print-server.js --name "PrintServer"
pm2 save
```

4. Manage the service:
```cmd
pm2 status          # Check status
pm2 restart PrintServer  # Restart
pm2 stop PrintServer     # Stop
pm2 logs PrintServer     # View logs
```

## Step 10: Test Print from Linux Server

SSH into your Linux server and test:

```bash
curl -X POST http://192.168.138.20:3000/print \
  -H "Content-Type: application/json" \
  -d '{
    "ticket": {
      "code": "CS-001",
      "service_type": "cashier",
      "priority": "student",
      "created_at": "2025-12-12T10:30:00Z"
    }
  }'
```

If successful, the printer should print a test ticket.

## Troubleshooting

### Print server won't start
- Check if port 3000 is already in use: `netstat -ano | findstr :3000`
- Check Node.js installation: `node --version`
- Check printer is online: `Get-Printer | Where-Object {$_.Name -eq "EPSON TM-T82II Receipt"}`

### Can't connect from Linux server
- Verify Windows firewall rule is active
- Ping Windows from Linux: `ping 192.168.138.20`
- Test health endpoint: `curl http://192.168.138.20:3000/health`
- Check Windows Defender isn't blocking connections

### Printer not printing
- Ensure printer is turned on and connected
- Check printer status in Windows: `Get-Printer | Format-List`
- Verify printer name matches in `print-server.js`
- Check printer queue for errors

### Service won't start automatically
- If using NSSM: Check service status in `services.msc`
- If using PM2: Run `pm2 save` after starting
- Check Windows Event Viewer for errors

## Monitoring

### View Print Server Logs

If using PM2:
```cmd
pm2 logs PrintServer
```

If using NSSM:
Check Event Viewer → Windows Logs → Application

### Check Service Status

```powershell
# For NSSM service
Get-Service PrintServer

# For PM2
pm2 status
```

## Network Configuration

Ensure both computers are on the same network:
- **Linux Server**: 192.168.138.30
- **Windows Client**: 192.168.138.20
- Subnet mask: 255.255.255.0 (typically)

To verify connectivity:
```bash
# From Linux
ping 192.168.138.20
curl http://192.168.138.20:3000/health
```

## Security Considerations

1. **Firewall**: Only allow connections from the Linux server IP
   ```powershell
   New-NetFirewallRule -DisplayName "Print Server from Linux" -Direction Inbound -LocalPort 3000 -Protocol TCP -Action Allow -RemoteAddress 192.168.138.30
   ```

2. **Authentication**: Consider adding API key authentication in production

3. **Network**: Ensure both servers are on a private network, not exposed to internet

## Maintenance

### Update Print Server
```cmd
cd C:\PrintServer
# Stop service first
pm2 stop PrintServer  # or stop NSSM service

# Update code
# Copy new print-server.js

# Restart
pm2 start PrintServer  # or start NSSM service
```

### Backup Configuration
Regularly backup:
- `C:\PrintServer\print-server.js`
- PM2 process list: `pm2 save`

---

**Support**: If issues persist, check the printer manual and ensure ESC/POS commands are compatible with your EPSON model.
