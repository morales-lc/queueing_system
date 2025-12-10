# Windows Print Server Setup for Queueing System

## Overview
This print server runs on the Windows PC (192.168.0.95) that has the receipt printer connected. It receives print jobs from the Ubuntu Laravel server (192.168.138.30) via HTTP and sends them to the local printer.

## Prerequisites
- Node.js 16+ installed on Windows PC
- Receipt printer (EPSON TM-T82II Receipt) connected and configured in Windows
- Windows 10/11

## Installation Steps

### 1. Install Node.js
Download and install Node.js from https://nodejs.org/ (LTS version recommended)

### 2. Navigate to Print Server Directory
```cmd
cd C:\path\to\queueing_system\print-server
```

### 3. Install Dependencies
```cmd
npm install
```

### 4. Configure Printer Name
Edit `print-server.js` and verify the printer name matches your Windows printer:
```javascript
const PRINTER_NAME = 'EPSON TM-T82II Receipt';
```

To check your printer name in Windows:
```cmd
wmic printer get name
```

### 5. Test the Server
```cmd
npm start
```

You should see:
```
Print Server running on port 3000
Configured for printer: EPSON TM-T82II Receipt
Server accessible at: http://192.168.0.95:3000
```

### 6. Test Print Server
Open browser and navigate to: `http://192.168.0.95:3000/health`

You should see:
```json
{"status":"ok","printer":"EPSON TM-T82II Receipt"}
```

## Running as Windows Service

To keep the print server running automatically, use `node-windows` or Task Scheduler.

### Option A: Using PM2 (Recommended)

1. Install PM2 globally:
```cmd
npm install -g pm2
```

2. Start the server with PM2:
```cmd
pm2 start print-server.js --name "print-server"
```

3. Save PM2 configuration:
```cmd
pm2 save
```

4. Setup PM2 to start on boot:
```cmd
pm2 startup
```

5. Follow the instructions displayed by PM2

### Option B: Using Windows Task Scheduler

1. Open Task Scheduler
2. Create Basic Task
3. Name: "Receipt Print Server"
4. Trigger: "When the computer starts"
5. Action: "Start a program"
6. Program: `C:\Program Files\nodejs\node.exe`
7. Arguments: `C:\path\to\print-server\print-server.js`
8. Start in: `C:\path\to\print-server`
9. Check "Run with highest privileges"

## Firewall Configuration

Allow incoming connections on port 3000:

```powershell
# Run as Administrator
New-NetFirewallRule -DisplayName "Print Server" -Direction Inbound -LocalPort 3000 -Protocol TCP -Action Allow
```

Or manually:
1. Open Windows Defender Firewall
2. Advanced settings
3. Inbound Rules → New Rule
4. Port → TCP → 3000
5. Allow the connection
6. Name: "Print Server"

## Testing from Ubuntu Server

From your Ubuntu server (192.168.138.30), test the connection:

```bash
curl http://192.168.0.95:3000/health
```

You should get:
```json
{"status":"ok","printer":"EPSON TM-T82II Receipt"}
```

## API Endpoints

### GET /health
Health check endpoint
```bash
curl http://192.168.0.95:3000/health
```

### POST /print
Send print job (ESC/POS commands as base64)
```bash
curl -X POST http://192.168.0.95:3000/print \
  -H "Content-Type: application/json" \
  -d '{"data":"base64_encoded_escpos_data"}'
```

### GET /printer/status
Check printer status
```bash
curl http://192.168.0.95:3000/printer/status
```

## Troubleshooting

### Port Already in Use
If port 3000 is already in use, edit `print-server.js`:
```javascript
const PORT = 3001; // Change to different port
```

### Printer Not Found
Run in Command Prompt:
```cmd
wmic printer get name
```
Copy the exact printer name and update in `print-server.js`

### Check Logs
When running with PM2:
```cmd
pm2 logs print-server
```

### Test Printer Connection
```cmd
powershell -Command "Get-Printer -Name 'EPSON TM-T82II Receipt'"
```

## Security Notes

- This server accepts connections from any IP. For production, add IP filtering in `print-server.js`
- Consider using HTTPS if handling sensitive data
- Keep Node.js updated for security patches

## Updates

To update the print server:
```cmd
cd C:\path\to\print-server
git pull
npm install
pm2 restart print-server
```
