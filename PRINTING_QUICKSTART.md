# Quick Start: Receipt Printing Setup

## Your Setup
- **Ubuntu Server**: `192.168.138.30` (http://louna.lccdo.edu.ph)
- **Windows PC**: `192.168.0.95` (with EPSON TM-T82II Receipt printer)

## Quick Steps

### On Windows PC (192.168.0.95)

1. **Install Node.js** from https://nodejs.org/

2. **Setup Print Server:**
   ```cmd
   cd C:\queueing_system\print-server
   npm install
   npm start
   ```

3. **Open Firewall:**
   ```powershell
   New-NetFirewallRule -DisplayName "Print Server" -Direction Inbound -LocalPort 3000 -Protocol TCP -Action Allow
   ```

4. **Test:**
   - Browser: http://localhost:3000/health

### On Ubuntu Server (192.168.138.30)

1. **Update .env:**
   ```bash
   sudo nano /var/www/html/queueing_system/.env
   ```
   Add:
   ```ini
   PRINTER_ENABLED=true
   PRINT_SERVER_URL=http://192.168.0.95:3000
   ```

2. **Clear cache:**
   ```bash
   sudo php artisan config:clear
   sudo php artisan config:cache
   sudo systemctl restart apache2
   ```

3. **Test from Ubuntu:**
   ```bash
   curl http://192.168.0.95:3000/health
   ```

## Files Created

- `/print-server/print-server.js` - Node.js print server
- `/print-server/package.json` - Dependencies
- `/print-server/README.md` - Detailed setup guide
- `/app/Services/PrintService.php` - Laravel print service
- `/app/Http/Controllers/KioskController_NEW.php` - Updated controller (rename to KioskController.php)
- `PRINTING_SETUP.md` - Complete printing setup documentation

## Next Steps

1. Close VS Code to release KioskController.php file lock
2. Rename `KioskController_NEW.php` to `KioskController.php`
3. Follow PRINTING_SETUP.md for production deployment

## Testing

Once setup:
1. Go to http://louna.lccdo.edu.ph/
2. Generate a ticket
3. Receipt should print on Windows PC

## Need Help?

See `PRINTING_SETUP.md` for detailed troubleshooting.
