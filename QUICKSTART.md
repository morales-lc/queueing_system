# Quick Start Guide - School Queueing System

## ✅ Complete! All features implemented:

### What's Done:
- ✅ Models: `QueueTicket`, `Counter` with migrations
- ✅ Controllers: Kiosk, Monitor, Counter with all actions
- ✅ Views: Bootstrap 5 UI for all 3 interfaces
- ✅ Real-time: Laravel Reverb broadcasting + Echo
- ✅ Printer: EPSON TM-T82II integration (escpos-php)
- ✅ Tests: Feature tests for core functionality
- ✅ Seeder: Cashier 1-4, Registrar 1-4 counters

---

## 🚀 First-Time Setup

### 1. Fix PowerShell (if npm blocked):
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### 2. Build Assets:
```powershell
npm run build
```

### 3. Verify `.env` settings:
```dotenv
BROADCAST_CONNECTION=reverb
PRINTER_ENABLED=true
PRINTER_TYPE=windows
PRINTER_TARGET="EPSON TM-T82II"
```

---

## ▶️ Running the System

### Start 3 terminals:

**Terminal 1 - Reverb (WebSocket server):**
```powershell
php artisan reverb:start
```

**Terminal 2 - Queue Worker (broadcasting):**
```powershell
php artisan queue:work
```

**Terminal 3 - Laravel Server:**
```powershell
php artisan serve
```

---

## 🌐 Access the Interfaces

Open in browser:
- **Kiosk**: http://localhost:8000/kiosk
- **TV Monitor**: http://localhost:8000/monitor
- **Operator**: http://localhost:8000/counter

---

## 🖨️ Printer Setup (EPSON TM-T82II)

### Windows (Print Spooler):
1. Install printer driver and ensure it's visible in "Printers & scanners"
2. Note exact printer name (e.g., "EPSON TM-T82II" or "TM-T82II Receipt")
3. Update `.env`:
```dotenv
PRINTER_ENABLED=true
PRINTER_TYPE=windows
PRINTER_TARGET="EPSON TM-T82II"
```

### Network Printing (optional):
```dotenv
PRINTER_ENABLED=true
PRINTER_TYPE=network
PRINTER_TARGET=192.168.1.100
PRINTER_PORT=9100
```

---

## 🧪 Testing

Run tests:
```powershell
php artisan test
```

---

## 📋 How It Works

### Kiosk Flow:
1. Select service: **Cashier** or **Registrar**
2. Select priority: **PWD/Senior/Pregnant**, **Student**, or **Parent**
3. System generates code (e.g., `CS-001` = Cashier-Student-001)
4. Print receipt with code + timestamp
5. Broadcast `ticket.created` event

### TV Monitor:
- Shows 8 windows (4 Cashier + 4 Registrar) in 2 columns
- Displays "now serving" code per window
- Listens to `ticket.serving` events via WebSocket
- Announces via TTS: "Now serving CS-001 at cashier window 1"

### Operator (Cashier/Registrar):
1. Select window (e.g., Cashier 1) - locks it for exclusive use
2. View next 5 tickets in priority order (PWD first → Student → Parent)
3. **Next**: Serve next pending ticket, broadcasts `ticket.serving`
4. **On Hold**: Put current ticket on hold if person unavailable
5. **Call Again**: Re-call a held ticket, broadcasts `ticket.serving` again
6. **Remove Hold**: Manually remove a held ticket
7. **Auto-cleanup**: Every 3 "Next" presses, oldest on-hold ticket auto-removed

### Counter Lock Mechanism:
- When operator selects a window, `claimed=true` in DB
- Window disabled for other users until "Exit" is clicked
- Prevents double-serving same ticket across counters

---

## 🔧 Troubleshooting

### WebSocket not connecting?
- Check `.env` has `BROADCAST_CONNECTION=reverb`
- Ensure Reverb server running: `php artisan reverb:start`
- Check browser console for WebSocket errors

### Printer not working?
- Verify printer name exactly matches Windows name
- Test: `php artisan tinker` then:
  ```php
  $c = new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector("EPSON TM-T82II");
  $p = new \Mike42\Escpos\Printer($c);
  $p->text("Test\n");
  $p->cut();
  $p->close();
  ```

### TTS not speaking?
- Enable sound in browser
- Check browser permissions for audio
- Test in browser console: `speechSynthesis.speak(new SpeechSynthesisUtterance('test'))`

---

## 🎯 Testing Workflow

1. **Issue tickets** at `/kiosk` (create 5-6 tickets)
2. **Claim window** at `/counter` (select Cashier 1)
3. **Press Next** multiple times - watch monitor update in real-time
4. **Test On Hold** - hold a ticket, verify it appears in hold list
5. **Call Again** - re-call held ticket, verify TTS announcement
6. **Open monitor** at `/monitor` in another window - verify live updates

---

## 📁 Key Files

- **Routes**: `routes/web.php`
- **Controllers**: `app/Http/Controllers/{Kiosk,Monitor,Counter}Controller.php`
- **Models**: `app/Models/{QueueTicket,Counter}.php`
- **Events**: `app/Events/TicketUpdated.php`
- **Views**: `resources/views/{kiosk,monitor,operator}/*.blade.php`
- **Tests**: `tests/Feature/QueueSystemTest.php`
- **Migrations**: `database/migrations/2025_12_03_*`

---

## ✨ Features Summary

- ✅ 3 priority levels with proper queue ordering
- ✅ Auto-generated codes (e.g., `CS-001`, `RP-002`)
- ✅ Real-time updates via Reverb WebSockets
- ✅ TTS announcements on monitor
- ✅ Thermal receipt printing (80mm, EPSON ESC/POS)
- ✅ On-hold queue with auto-removal after 3 nexts
- ✅ Window locking (prevents double-serving)
- ✅ Bootstrap 5 responsive UI
- ✅ No authentication required (kiosk/operator)

---

**System is ready to use!** Start the 3 terminals and open the URLs above.
