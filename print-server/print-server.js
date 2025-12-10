const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const { exec } = require('child_process');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = 3000;

// Middleware
app.use(cors());
app.use(bodyParser.json({ limit: '10mb' }));
app.use(bodyParser.urlencoded({ extended: true, limit: '10mb' }));

// Windows printer name (must match exactly as shown in Windows)
const PRINTER_NAME = 'EPSON TM-T82II Receipt';

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ status: 'ok', printer: PRINTER_NAME });
});

// Print endpoint - receives ESC/POS commands as base64
app.post('/print', async (req, res) => {
    try {
        const { data } = req.body;

        if (!data) {
            return res.status(400).json({ error: 'No print data provided' });
        }

        // Decode base64 data
        const buffer = Buffer.from(data, 'base64');

        // Create temp file for raw print data
        const tempFile = path.join(__dirname, `print_${Date.now()}.prn`);
        fs.writeFileSync(tempFile, buffer);

        // Send raw data to printer using Windows print command
        // Using 'print' command which supports raw printing
        const command = `print /D:"${PRINTER_NAME}" "${tempFile}"`;

        exec(command, (error, stdout, stderr) => {
            // Clean up temp file
            try {
                fs.unlinkSync(tempFile);
            } catch (e) {
                console.error('Error deleting temp file:', e);
            }

            if (error) {
                console.error('Print error:', error);
                return res.status(500).json({ 
                    error: 'Print failed', 
                    details: error.message 
                });
            }

            console.log('Print job sent successfully');
            res.json({ success: true, message: 'Print job sent to printer' });
        });

    } catch (error) {
        console.error('Server error:', error);
        res.status(500).json({ 
            error: 'Server error', 
            details: error.message 
        });
    }
});

// Check printer status endpoint
app.get('/printer/status', (req, res) => {
    const command = `powershell -Command "Get-Printer -Name '${PRINTER_NAME}' | Select-Object Name, PrinterStatus, JobCount | ConvertTo-Json"`;
    
    exec(command, (error, stdout, stderr) => {
        if (error) {
            return res.status(500).json({ 
                error: 'Failed to get printer status',
                details: error.message 
            });
        }

        try {
            const status = JSON.parse(stdout);
            res.json({ 
                online: status.PrinterStatus === 0 || status.PrinterStatus === 'Normal',
                status: status,
                printer: PRINTER_NAME
            });
        } catch (e) {
            res.status(500).json({ 
                error: 'Failed to parse printer status',
                details: e.message 
            });
        }
    });
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Print Server running on port ${PORT}`);
    console.log(`Configured for printer: ${PRINTER_NAME}`);
    console.log(`Server accessible at: http://192.168.0.95:${PORT}`);
});
