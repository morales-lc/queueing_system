from flask import Flask, request, jsonify
from datetime import datetime
from PIL import Image
import win32print, win32api

PRINTER_NAME = "EPSON TM-T82II Receipt"

app = Flask(__name__)


@app.get("/health")
def health():
    """Return real printer health status.

    Used by the Laravel kiosk to decide whether users may generate tickets.
    """
    try:
        # Try to open the printer
        hPrinter = win32print.OpenPrinter(PRINTER_NAME)
    except Exception as e:
        # Cannot open printer: treat as offline / not connected
        return (
            jsonify(
                {
                    "printer": PRINTER_NAME,
                    "can_print": False,
                    "issues": ["cannot_open_printer"],
                    "details": str(e),
                }
            ),
            503,
        )

    try:
        # Query detailed printer info (PRINTER_INFO_2)
        info = win32print.GetPrinter(hPrinter, 2)

        # pywin32 may return either a dict-like object or a tuple.
        # Prefer the named 'Status' field when available; fall back to
        # the legacy index 18 only if needed.
        if isinstance(info, dict):
            status = int(info.get("Status", 0) or 0)
        else:
            # PRINTER_INFO_2 layout: Status is field index 18
            # https://learn.microsoft.com/windows/win32/printdocs/printer-info-2
            status = int(info[18]) if len(info) > 18 else 0

        # Relevant status bits
        PRINTER_STATUS_PAUSED = 0x00000001
        PRINTER_STATUS_ERROR = 0x00000002
        PRINTER_STATUS_PAPER_JAM = 0x00000008
        PRINTER_STATUS_PAPER_OUT = 0x00000010
        PRINTER_STATUS_PAPER_PROBLEM = 0x00000040
        PRINTER_STATUS_OFFLINE = 0x00000080
        PRINTER_STATUS_PRINTING = 0x00000400
        PRINTER_STATUS_NOT_AVAILABLE = 0x00001000
        PRINTER_STATUS_DOOR_OPEN = 0x00400000 #printer open 

        issues = []
        if status & PRINTER_STATUS_OFFLINE:
            issues.append("offline")
        if status & PRINTER_STATUS_PAPER_OUT:
            issues.append("paper_out")
        if status & PRINTER_STATUS_PAPER_JAM:
            issues.append("paper_jam")
        if status & PRINTER_STATUS_DOOR_OPEN:
            issues.append("door_open")         
        if status & PRINTER_STATUS_PAPER_PROBLEM:
            issues.append("paper_problem")
        if status & PRINTER_STATUS_ERROR:
            issues.append("error")
        if status & PRINTER_STATUS_NOT_AVAILABLE:
            issues.append("not_available")

        can_print = len(issues) == 0

        return jsonify(
            {
                "printer": PRINTER_NAME,
                "can_print": can_print,
                "issues": issues,
                "raw_status": status,
            }
        )
    except Exception as e:
        # Any failure while querying status: be conservative and block printing
        return (
            jsonify(
                {
                    "printer": PRINTER_NAME,
                    "can_print": False,
                    "issues": ["status_query_failed"],
                    "details": str(e),
                }
            ),
            503,
        )
    finally:
        try:
            win32print.ClosePrinter(hPrinter)
        except Exception:
            pass


def escpos_image(path):
    img = Image.open(path)

    # Grayscale
    img = img.convert("L")

    # Resize logo smaller
    TARGET_WIDTH = 200  # adjust as needed
    scale = TARGET_WIDTH / img.width
    img = img.resize((TARGET_WIDTH, int(img.height * scale)))

    # Convert to pure B/W
    img = img.point(lambda x: 0 if x < 160 else 255, "1")

    # Invert ONLY image bits (not text)
    img = Image.eval(img, lambda x: 255 - x)

    w, h = img.size

    # Width must be divisible by 8
    if w % 8 != 0:
        img = img.resize(((w + 7) // 8 * 8, h))
        w = img.width

    # GS v 0 raster image command
    header = bytes([0x1D, 0x76, 0x30, 0x00])

    width_bytes = w // 8
    xL = width_bytes & 0xFF
    xH = (width_bytes >> 8) & 0xFF
    yL = h & 0xFF
    yH = (h >> 8) & 0xFF

    return header + bytes([xL, xH, yL, yH]) + img.tobytes()







def escpos_ticket(ticket):

    raw_time = ticket["created_at"]
    dt = datetime.fromisoformat(raw_time.replace("Z", "+00:00"))
    readable = dt.strftime("%b %d, %Y %I:%M %p")  # shorter date format

    esc = lambda *b: bytes(b)
    lines = []

    # Init + center
    lines.append(esc(0x1B, 0x40))
    lines.append(esc(0x1B, 0x61, 0x01))

    # ------------------------------
    # LOGO
    # ------------------------------
    try:
        img_bytes = escpos_image("Lourdes_final.png")
        lines.append(img_bytes)
        lines.append(b"\n")

        # Reset after image
        lines.append(esc(0x1B, 0x40))
        lines.append(esc(0x1B, 0x61, 0x01))
    except Exception as e:
        print("Image error:", e)

    # ------------------------------
    # QUEUE NUMBER (MAIN FOCUS)
    # ------------------------------
    lines.append(esc(0x1D, 0x21, 0x22))     # double size
    lines.append((ticket["code"] + "\n").encode("ascii"))

    # Normal text
    lines.append(esc(0x1D, 0x21, 0x00))

    # Minimal info
    lines.append(("Service: " + ticket["service_type"].capitalize() + "\n").encode("ascii"))
    lines.append(("Time: " + readable + "\n").encode("ascii"))

    # Footer (compact)
    lines.append(b"\nPlease wait for your number\n")
    lines.append(b"\n")

    # Cut
    lines.append(esc(0x1D, 0x56, 0x42, 0x00))

    return b"".join(lines)



@app.post("/print")
def print_ticket():
    data = request.get_json(silent=True) or {}
    ticket = data.get("ticket")
    if not ticket:
        return jsonify({"error":"Missing ticket data"}), 400
    raw = escpos_ticket(ticket)
    hPrinter = win32print.OpenPrinter(PRINTER_NAME)
    try:
        hJob = win32print.StartDocPrinter(hPrinter, 1, ("Queue Ticket", None, "RAW"))
        win32print.StartPagePrinter(hPrinter)
        win32print.WritePrinter(hPrinter, raw)
        win32print.EndPagePrinter(hPrinter)
        win32print.EndDocPrinter(hPrinter)
        return jsonify({"success":True,"job_id":hJob})
    except Exception as e:
        return jsonify({"error":"Printing failed","details":str(e)}), 500
    finally:
        win32print.ClosePrinter(hPrinter)

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=3000)