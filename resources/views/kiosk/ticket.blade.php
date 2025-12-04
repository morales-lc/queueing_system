<!doctype html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Your Ticket</title>
</head>
<body class="p-4">
<div class="container text-center">
    <h2>Your Queue Code</h2>
    <div class="display-1 fw-bold">{{ $ticket->code }}</div>
    <p>Generated: {{ $ticket->created_at->format('F j, Y g:i A') }}</p>
    <p>Service: {{ ucfirst($ticket->service_type) }} | Priority: {{ str_replace('_',' ', ucfirst($ticket->priority)) }}</p>
    <a class="btn btn-secondary" href="{{ route('kiosk.index') }}">Back</a>
    <script>
        // Placeholder for triggering print at kiosk; implement server-side printing
        window.print();
    </script>
</div>
</body>
</html>
