/**
 * Receipt Printer HTTP Server for Windows 11
 * 
 * This Node.js server receives print jobs from the Laravel queueing system
 * and prints them to the local EPSON TM-T82II Receipt printer.
 * 
 * Installation:
 * 1. Install Node.js from https://nodejs.org/
 * 2. Run: npm install
 * 3. Test: node print-server.js
 * 4. Install as Windows service using NSSM (see PRINTING_SETUP.md)
 */

const express = require('express');
const bodyParser = require('body-parser');
const { ThermalPrinter, PrinterTypes } = require('node-thermal-printer');
const cors = require('cors');

const app = express();

// Middleware
app.use(cors()); // Allow requests from Laravel server
app.use(bodyParser.json());
app.use(bodyParser.text({ type: 'text/plain', limit: '10mb' }));

// Logging middleware
app.use((req, res, next) => {
    console.log(`[${new Date().toISOString()}] ${req.method} ${req.path}`);
    next();
});

// Configuration - UPDATE THIS WITH YOUR PRINTER NAME
const PRINTER_NAME = 'EPSON TM-T82II Receipt';
const PORT = 3000;

// Configure thermal printer
function createPrinter() {
    return new ThermalPrinter({
        type: PrinterTypes.EPSON,
        interface: `printer:${PRINTER_NAME}`,
        characterSet: 'PC437_USA',
        removeSpecialCharacters: false,
        lineCharacter: "=",
        breakLine: "\n",
        options: {
            timeout: 5000
        }
    });
}

/**
 * POST /print - Print a queue ticket
 * 
 * Expected JSON body:
 * {
 *   "code": "CS-001",
 *   "service": "Cashier",
 *   "priority": "Student",
 *   "time": "Dec. 9, 2025 10:30 AM",
 *   "logo": true
 * }
 */
app.post('/print', async (req, res) => {
    try {
        console.log('Received print job:', JSON.stringify(req.body, null, 2));
        
        const printData = req.body;
        
        // Validate required fields
        if (!printData.code) {
            return res.status(400).json({ 
                success: false, 
                error: 'Missing required field: code' 
            });
        }
        
        const printer = createPrinter();
        
        // Clear any previous data
        printer.clear();
        
        // Print logo/header
        if (printData.logo) {
            printer.alignCenter();
            printer.bold(true);
            printer.println('LOURDES COLLEGE, INC.');
            printer.bold(false);
            printer.newLine();
        }
        
        // Print "Your Queue Code" text
        printer.alignCenter();
        printer.println('Your Queue Code');
        printer.newLine();
        
        // Print ticket number - LARGE AND BOLD
        printer.setTextSize(2, 2); // Double width and height
        printer.bold(true);
        printer.println(printData.code || 'TICKET');
        printer.bold(false);
        printer.setTextNormal();
        printer.newLine();
        
        // Print timestamp
        if (printData.time) {
            printer.setTextSize(0, 0); // Normal size
            printer.println(printData.time);
            printer.newLine();
        }
        
        // Print service type
        if (printData.service) {
            printer.bold(true);
            printer.println('Service: ' + printData.service);
            printer.bold(false);
        }
        
        // Print priority (optional, if you want to include it)
        // if (printData.priority) {
        //     printer.println('Priority: ' + printData.priority);
        // }
        
        printer.newLine();
        printer.newLine();
        
        // Cut paper
        printer.cut();
        
        // Send to printer
        await printer.execute();
        
        console.log('✓ Print job completed successfully');
        res.json({ 
            success: true, 
            message: 'Printed successfully',
            ticket: printData.code
        });
        
    } catch (error) {
        console.error('✗ Print error:', error.message);
        console.error('Stack trace:', error.stack);
        
        res.status(500).json({ 
            success: false, 
            error: error.message,
            details: 'Check that the printer is connected and powered on'
        });
    }
});

/**
 * GET /status - Check server and printer status
 */
app.get('/status', (req, res) => {
    try {
        res.json({ 
            status: 'online',
            server: 'Receipt Print Server',
            printer: PRINTER_NAME,
            timestamp: new Date().toISOString(),
            version: '1.0.0'
        });
    } catch (error) {
        res.status(500).json({ 
            status: 'error', 
            error: error.message 
        });
    }
});

/**
 * GET /test - Print a test ticket
 */
app.get('/test', async (req, res) => {
    try {
        const printer = createPrinter();
        
        printer.clear();
        printer.alignCenter();
        printer.bold(true);
        printer.println('TEST PRINT');
        printer.bold(false);
        printer.newLine();
        printer.setTextSize(2, 2);
        printer.println('TEST-001');
        printer.setTextNormal();
        printer.newLine();
        printer.println(new Date().toLocaleString());
        printer.newLine();
        printer.println('If you can read this,');
        printer.println('the printer is working!');
        printer.newLine();
        printer.cut();
        
        await printer.execute();
        
        res.json({ 
            success: true, 
            message: 'Test print sent successfully' 
        });
    } catch (error) {
        res.status(500).json({ 
            success: false, 
            error: error.message 
        });
    }
});

/**
 * GET / - Welcome page
 */
app.get('/', (req, res) => {
    res.send(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt Print Server</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                h1 { color: #333; }
                .status { color: #4CAF50; font-weight: bold; }
                .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .button { display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
                .button:hover { background: #1976D2; }
                code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🖨️ Receipt Print Server</h1>
                <p class="status">✓ Server is running</p>
                
                <div class="info">
                    <strong>Printer:</strong> ${PRINTER_NAME}<br>
                    <strong>Port:</strong> ${PORT}<br>
                    <strong>Status:</strong> Online
                </div>
                
                <h2>Available Endpoints</h2>
                <ul>
                    <li><code>POST /print</code> - Print a queue ticket</li>
                    <li><code>GET /status</code> - Check server status</li>
                    <li><code>GET /test</code> - Print a test ticket</li>
                </ul>
                
                <h2>Quick Actions</h2>
                <a href="/status" class="button">Check Status</a>
                <a href="/test" class="button">Print Test Ticket</a>
                
                <h2>Configuration</h2>
                <p>Add this to your Laravel <code>.env</code> file:</p>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;">
PRINTER_ENABLED=true
PRINTER_TYPE=http
PRINTER_TARGET=http://YOUR_WINDOWS_IP:${PORT}/print
                </pre>
            </div>
        </body>
        </html>
    `);
});

// Error handling middleware
app.use((error, req, res, next) => {
    console.error('Server error:', error);
    res.status(500).json({ 
        success: false, 
        error: 'Internal server error',
        message: error.message 
    });
});

// Start server
app.listen(PORT, '0.0.0.0', () => {
    console.log('╔════════════════════════════════════════════════════════╗');
    console.log('║      Receipt Print Server - Windows 11                 ║');
    console.log('╚════════════════════════════════════════════════════════╝');
    console.log('');
    console.log(`✓ Server running on: http://0.0.0.0:${PORT}`);
    console.log(`✓ Printer: ${PRINTER_NAME}`);
    console.log(`✓ Ready to receive print jobs from Laravel`);
    console.log('');
    console.log('Endpoints:');
    console.log(`  - Status:  http://localhost:${PORT}/status`);
    console.log(`  - Test:    http://localhost:${PORT}/test`);
    console.log(`  - Print:   POST http://localhost:${PORT}/print`);
    console.log('');
    console.log('Press Ctrl+C to stop');
    console.log('════════════════════════════════════════════════════════');
});

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('\n\nShutting down print server...');
    process.exit(0);
});
