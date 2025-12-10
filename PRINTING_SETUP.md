# Receipt Printing Setup Guide

## Architecture Overview

**Server (Ubuntu)**: `192.168.138.30` - http://louna.lccdo.edu.ph  
**Print Client (Windows)**: `192.168.0.95` - Runs Node.js print server  
**Printer**: EPSON TM-T82II Receipt (connected to Windows PC)

The Laravel application on Ubuntu sends print jobs via HTTP to the Windows print server, which then sends the data to the locally connected printer.

## Part 1: Windows Print Server Setup

### Step 1: Install Node.js on Windows PC (192.168.0.95)

1. Download Node.js LTS from https://nodejs.org/
2. Run the installer (accept all defaults)
3. Verify installation:
   ```cmd
   node --version
   npm --version
   ```

### Step 2: Clone/Copy Print Server Files

If you have Git on Windows:
```cmd
cd C:\
git clone https://github.com/morales-lc/queueing_system.git
cd queueing_system\print-server
```

Or manually copy the `print-server` folder to `C:\queueing_system\print-server\`

### Step 3: Install Dependencies

```cmd
cd C:\queueing_system\print-server
npm install
```

### Step 4: Verify Printer Name

Check your exact printer name:
```cmd
wmic printer get name
```

Or:
```powershell
Get-Printer | Select-Object Name
```

If your printer name is different from "EPSON TM-T82II Receipt", edit `print-server.js` and update:
```javascript
const PRINTER_NAME = 'Your Exact Printer Name Here';
```

### Step 5: Test the Print Server

```cmd
cd C:\queueing_system\print-server
npm start
```

You should see:
```
Print Server running on port 3000
Configured for printer: EPSON TM-T82II Receipt
Server accessible at: http://192.168.0.95:3000
```

### Step 6: Test From Browser

On the Windows PC, open browser and go to:
```
http://localhost:3000/health
```

You should see:
```json
{"status":"ok","printer":"EPSON TM-T82II Receipt"}
```

### Step 7: Configure Windows Firewall

**Option A: Using PowerShell (Administrator)**
```powershell
New-NetFirewallRule -DisplayName "Print Server Port 3000" -Direction Inbound -LocalPort 3000 -Protocol TCP -Action Allow
```

**Option B: Using GUI**
1. Open "Windows Defender Firewall with Advanced Security"
2. Click "Inbound Rules"
3. Click "New Rule..."
4. Select "Port" → Next
5. TCP, Specific local ports: `3000` → Next
6. Allow the connection → Next
7. Select all profiles (Domain, Private, Public) → Next
8. Name: "Print Server" → Finish

### Step 8: Test From Ubuntu Server

From your Ubuntu server (192.168.138.30), test the connection:

```bash
curl http://192.168.0.95:3000/health
```

Expected response:
```json
{"status":"ok","printer":"EPSON TM-T82II Receipt"}
```

If this works, the network connection is good!

### Step 9: Run Print Server as Windows Service

**Using PM2 (Recommended):**

1. Install PM2 globally:
   ```cmd
   npm install -g pm2
   ```

2. Install PM2 as Windows service:
   ```cmd
   npm install -g pm2-windows-service
   pm2-service-install
   ```

3. Start the print server:
   ```cmd
   cd C:\queueing_system\print-server
   pm2 start print-server.js --name receipt-printer
   pm2 save
   ```

4. Verify it's running:
   ```cmd
   pm2 list
   pm2 logs receipt-printer
   ```

The print server will now start automatically when Windows boots.

**Alternative: Using Task Scheduler:**

1. Open Task Scheduler
2. Create Basic Task
3. Name: "Receipt Print Server"
4. Trigger: "When the computer starts"
5. Action: "Start a program"
6. Program/script: `C:\Program Files\nodejs\node.exe`
7. Add arguments: `C:\queueing_system\print-server\print-server.js`
8. Start in: `C:\queueing_system\print-server`
9. Check "Run with highest privileges"
10. Check "Run whether user is logged on or not"

## Part 2: Ubuntu Laravel Server Configuration

### Step 1: Update Environment Variables

SSH into your Ubuntu server and edit the `.env` file:

```bash
cd /var/www/html/queueing_system
sudo nano .env
```

Add/update these lines:

```ini
# Enable printing
PRINTER_ENABLED=true

# Print server URL (Windows PC IP)
PRINT_SERVER_URL=http://192.168.0.95:3000
```

Save and exit (Ctrl+X, Y, Enter)

### Step 2: Clear Configuration Cache

```bash
sudo php artisan config:clear
sudo php artisan config:cache
```

### Step 3: Restart Services

```bash
sudo systemctl restart apache2
sudo systemctl restart reverb
```

### Step 4: Test Printing From Laravel

Create a test route or use Laravel Tinker:

```bash
sudo php artisan tinker
```

Then test:
```php
$service = new \App\Services\PrintService();
$service->checkHealth();
// Should return: true

$service->checkPrinterStatus();
// Should return: array with 'online' => true
```

## Part 3: Testing the Complete Flow

### Test 1: Health Check

From Ubuntu server:
```bash
curl http://192.168.0.95:3000/health
```

### Test 2: Printer Status

```bash
curl http://192.168.0.95:3000/printer/status
```

### Test 3: Full Ticket Print

1. Open the kiosk page: http://louna.lccdo.edu.ph/
2. Select a service (Cashier or Registrar)
3. Select priority
4. Click to issue ticket
5. Check if receipt prints on Windows PC

### Test 4: Check Logs

**On Ubuntu:**
```bash
sudo tail -f /var/www/html/queueing_system/storage/logs/laravel.log
```

**On Windows:**
If using PM2:
```cmd
pm2 logs receipt-printer
```

## Troubleshooting

### Issue: Cannot connect to print server

**Check 1: Is print server running?**
On Windows:
```cmd
pm2 list
```
or check Task Manager for node.exe

**Check 2: Firewall blocking?**
Temporarily disable Windows Firewall to test:
```powershell
Set-NetFirewallProfile -Profile Domain,Public,Private -Enabled False
```
If printing works, re-enable firewall and add proper rule.

**Check 3: Network connectivity**
From Ubuntu:
```bash
ping 192.168.0.95
telnet 192.168.0.95 3000
```

### Issue: Printer not found

```cmd
wmic printer get name
```
Copy exact name into `print-server.js`

### Issue: Print job sent but nothing prints

**Check printer queue:**
```cmd
powershell -Command "Get-PrintJob -PrinterName 'EPSON TM-T82II Receipt'"
```

**Clear print queue:**
```cmd
powershell -Command "Get-PrintJob -PrinterName 'EPSON TM-T82II Receipt' | Remove-PrintJob"
```

**Restart Print Spooler:**
```cmd
net stop spooler
net start spooler
```

### Issue: Laravel logs show "Print server health check failed"

1. Check if print server is running on Windows
2. Check firewall allows port 3000
3. Verify IP address is correct in `.env`
4. Test with curl from Ubuntu

### Issue: Permission denied on Windows

Run print server as Administrator or adjust printer sharing permissions.

## Monitoring & Maintenance

### Check Print Server Status

**From Windows:**
```cmd
pm2 list
pm2 logs receipt-printer --lines 100
```

**From Ubuntu:**
```bash
curl http://192.168.0.95:3000/health
curl http://192.168.0.95:3000/printer/status
```

### Restart Print Server

**If using PM2:**
```cmd
pm2 restart receipt-printer
```

**If using Task Scheduler:**
- Stop the task
- Start the task
- Or reboot Windows PC

### Update Print Server Code

```cmd
cd C:\queueing_system\print-server
git pull
npm install
pm2 restart receipt-printer
```

## Network Requirements

- Windows PC must be on same network as Ubuntu server
- Port 3000 must be accessible from Ubuntu server to Windows PC
- Both machines should have static IP addresses or DHCP reservations
- Consider router firewall rules if on different VLANs

## Security Considerations

1. **IP Restriction**: Edit `print-server.js` to only accept connections from Ubuntu server:
   ```javascript
   app.use((req, res, next) => {
       const allowedIP = '192.168.138.30';
       if (req.ip !== allowedIP && req.ip !== `::ffff:${allowedIP}`) {
           return res.status(403).json({ error: 'Forbidden' });
       }
       next();
   });
   ```

2. **HTTPS**: For production, consider adding SSL/TLS

3. **Authentication**: Add API key authentication if needed

## Alternative: If Windows PC Changes IP

If the Windows PC gets a different IP, update on Ubuntu:

```bash
sudo nano /var/www/html/queueing_system/.env
# Change PRINT_SERVER_URL
sudo php artisan config:clear
sudo php artisan config:cache
sudo systemctl restart apache2
```

## Support

For issues:
1. Check Laravel logs: `/var/www/html/queueing_system/storage/logs/laravel.log`
2. Check print server logs: `pm2 logs receipt-printer`
3. Test health endpoint: `curl http://192.168.0.95:3000/health`
4. Verify printer: `wmic printer get name,printerstatus`
