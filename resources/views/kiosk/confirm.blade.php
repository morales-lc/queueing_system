<!doctype html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Confirm</title>
</head>
<body class="p-4">
<div class="container">
    <h2>Confirm Selection</h2>
    <p>Service: <strong>{{ ucfirst($service) }}</strong></p>
    <p>Priority: <strong>{{ str_replace('_',' ', ucfirst($priority)) }}</strong></p>
    <form method="post" action="{{ route('kiosk.issue') }}" class="mt-3">
        @csrf
        <input type="hidden" name="service" value="{{ $service }}">
        <input type="hidden" name="priority" value="{{ $priority }}">
        <button class="btn btn-success btn-lg">Generate Code</button>
    </form>
</div>
</body>
</html>
