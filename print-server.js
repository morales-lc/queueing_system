/**
 * Windows Print Server for EPSON TM-T82II Receipt Printer
 * 
 * This Node.js server runs on Windows 11 (IP: 192.168.138.20)
 * and receives print requests from the Linux server via HTTP.
 * 
 * Setup Instructions:
 * 1. Install Node.js on Windows 11
 * 2. Run: npm install express escpos escpos-windows body-parser
 * 3. Update printer name if different
 * 4. Run: node print-server.js
 * 5. Keep this running or set up as Windows Service
 */

const express = require('express');
const bodyParser = require('body-parser');
// Use Windows system printer via RAW jobs
const printer = require('printer');

const app = express();
const PORT = 3000;

// Middleware
app.use(bodyParser.json({ limit: '10mb' }));
app.use(bodyParser.urlencoded({ extended: true, limit: '10mb' }));

// CORS headers to allow requests from Linux server
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    if (req.method === 'OPTIONS') {
        return res.sendStatus(200);
    }
    next();
});

// Printer configuration
const PRINTER_NAME = 'EPSON TM-T82II Receipt';

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        status: 'online',
        printer: PRINTER_NAME,
        timestamp: new Date().toISOString()
    });
});

// Print endpoint
app.post('/print', async (req, res) => {
    try {
        console.log('Received print request at', new Date().toISOString());
        
        const { ticket } = req.body;
        
        if (!ticket) {
            return res.status(400).json({ error: 'Missing ticket data' });
        }
        
        console.log('Printing ticket:', ticket.code);

        // Build a simple ESC/POS receipt in RAW data
        const esc = {
            init: Buffer.from([0x1B, 0x40]), // Initialize
            center: Buffer.from([0x1B, 0x61, 0x01]),
            left: Buffer.from([0x1B, 0x61, 0x00]),
            bold_on: Buffer.from([0x1B, 0x45, 0x01]),
            bold_off: Buffer.from([0x1B, 0x45, 0x00]),
            size_normal: Buffer.from([0x1D, 0x21, 0x00]),
            size_double: Buffer.from([0x1D, 0x21, 0x11]),
            size_quad: Buffer.from([0x1D, 0x21, 0x22]),
            cut: Buffer.from([0x1D, 0x56, 0x42, 0x00]),
            lf: Buffer.from([0x0A])
        };

        const lines = [];
        lines.push(esc.init);
        lines.push(esc.center);
        lines.push(esc.bold_on);
        lines.push(esc.size_normal);
        lines.push(Buffer.from('LOURDES COLLEGE, INC.\n'));
        lines.push(Buffer.from('Queue Management System\n'));
        lines.push(Buffer.from('================================\n'));
        lines.push(esc.size_double);
        lines.push(Buffer.from('\nYour Number\n\n'));
        lines.push(esc.size_quad);
        lines.push(Buffer.from(String(ticket.code) + '\n'));
        lines.push(esc.size_normal);
        lines.push(esc.bold_off);
        lines.push(Buffer.from('\n================================\n'));
        lines.push(Buffer.from('Service: ' + capitalizeFirst(ticket.service_type) + '\n'));
        lines.push(Buffer.from('Priority: ' + formatPriority(ticket.priority) + '\n'));
        lines.push(Buffer.from('Time: ' + new Date(ticket.created_at).toLocaleString('en-PH', {
            timeZone: 'Asia/Manila',
            dateStyle: 'short',
            timeStyle: 'short'
        }) + '\n'));
        lines.push(Buffer.from('================================\n\n'));
        lines.push(Buffer.from('Please wait for your number\n'));
        lines.push(Buffer.from('to be called on the monitor.\n\n'));
        lines.push(Buffer.from('Thank you!\n\n\n'));
        lines.push(esc.cut);

        const rawData = Buffer.concat(lines);

        try {
            printer.printDirect({
                data: rawData,
                printer: PRINTER_NAME,
                type: 'RAW',
                success: function(jobID) {
                    console.log('Print job sent. ID:', jobID);
                    res.json({ success: true, message: 'Ticket printed successfully', ticket_code: ticket.code, job_id: jobID });
                },
                error: function(err) {
                    console.error('Printing failed:', err);
                    res.status(500).json({ error: 'Printing failed', details: String(err) });
                }
            });
        } catch (e) {
            console.error('Unexpected printing error:', e);
            return res.status(500).json({ error: 'Unexpected printing error', details: e.message });
        }
        
    } catch (error) {
        console.error('Error processing print request:', error);
        res.status(500).json({ error: 'Internal server error', details: error.message });
    }
});

// Helper functions
function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatPriority(priority) {
    const priorities = {
        'pwd_senior_pregnant': 'PWD/Senior/Pregnant',
        'student': 'Student',
        'parent': 'Parent/Guardian'
    };
    return priorities[priority] || priority;
}

// Start server
app.listen(PORT, '0.0.0.0', () => {
    console.log('='.repeat(50));
    console.log('Windows Print Server Started');
    console.log('='.repeat(50));
    console.log(`Server running on: http://0.0.0.0:${PORT}`);
    console.log(`Printer: ${PRINTER_NAME}`);
    console.log(`Access from network: http://192.168.138.20:${PORT}`);
    console.log('');
    console.log('Endpoints:');
    console.log(`  GET  /health - Health check`);
    console.log(`  POST /print  - Print ticket`);
    console.log('='.repeat(50));
    console.log('Waiting for print requests from Linux server...');
});

// Handle graceful shutdown
process.on('SIGINT', () => {
    console.log('\nShutting down print server...');
    process.exit(0);
});
