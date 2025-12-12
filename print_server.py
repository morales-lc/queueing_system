"""
Python Windows Print Server for EPSON TM-T82II Receipt

Runs on Windows 10/11 (IP: 192.168.138.20) and accepts
HTTP POST /print from the Linux Laravel server.

Expected JSON payload from Laravel:
{
  "ticket": {
    "code": "CS-001",
    "service_type": "cashier",
    "priority": "student",
    "created_at": "2025-12-12T10:30:00+08:00"
  }
}
"""

from flask import Flask, request, jsonify
import win32print
import win32api

PRINTER_NAME = "EPSON TM-T82II Receipt"

app = Flask(__name__)


@app.get("/health")
def health():
    return jsonify({
        "status": "online",
        "printer": PRINTER_NAME,
    })


def build_escpos(ticket: dict) -> bytes:
    """Build a simple ESC/POS receipt for the given ticket."""
    esc = lambda *b: bytes(b)
    lines = []
    # Initialize
    lines.append(esc(0x1B, 0x40))
    # Center
    lines.append(esc(0x1B, 0x61, 0x01))
    lines.append(b"LOURDES COLLEGE, INC.\n")
    lines.append(b"Queue Management System\n")
    lines.append(b"================================\n")
    # "Your Number"
    lines.append(esc(0x1D, 0x21, 0x11))  # double size
    lines.append(b"\nYour Number\n\n")
    # Ticket code big
    lines.append(esc(0x1D, 0x21, 0x22))  # quad size
    code = str(ticket.get("code", "")).encode("ascii", errors="ignore")
    lines.append(code + b"\n")
    # Back to normal
    lines.append(esc(0x1D, 0x21, 0x00))
    lines.append(b"\n================================\n")

    service = str(ticket.get("service_type", "")).capitalize()
    priority = str(ticket.get("priority", ""))
    created_at = str(ticket.get("created_at", ""))

    lines.append(f"Service: {service}\n".encode("ascii", errors="ignore"))
    lines.append(f"Priority: {priority}\n".encode("ascii", errors="ignore"))
    lines.append(f"Time: {created_at}\n".encode("ascii", errors="ignore"))
    lines.append(b"================================\n\n")
    lines.append(b"Please wait for your number\n")
    lines.append(b"to be called on the monitor.\n\n")
    lines.append(b"Thank you!\n\n\n")
    # Cut
    lines.append(esc(0x1D, 0x56, 0x42, 0x00))

    return b"".join(lines)


@app.post("/print")
def print_ticket():
    data = request.get_json(silent=True) or {}
    ticket = data.get("ticket")
    if not ticket:
        return jsonify({"error": "Missing ticket data"}), 400

    try:
        raw_data = build_escpos(ticket)
        hprinter = win32print.OpenPrinter(PRINTER_NAME)
        try:
            job_id = win32print.StartDocPrinter(hprinter, 1, ("Queue Ticket", None, "RAW"))
            win32print.StartPagePrinter(hprinter)
            win32print.WritePrinter(hprinter, raw_data)
            win32print.EndPagePrinter(hprinter)
            win32print.EndDocPrinter(hprinter)
        finally:
            win32print.ClosePrinter(hprinter)
        return jsonify({"success": True, "job_id": job_id})
    except Exception as e:  # noqa: BLE001
        return jsonify({"error": "Printing failed", "details": str(e)}), 500


if __name__ == "__main__":
    # Listen on all interfaces, port 3000 (same as Node example)
    app.run(host="0.0.0.0", port=3000)
