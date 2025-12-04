<!doctype html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Priority</title>
</head>
<body class="p-4">
<div class="container">
    <h2>Select Priority ({{ ucfirst($service) }})</h2>
    <form method="post" action="{{ route('kiosk.priority') }}" class="mt-3">
        @csrf
        <input type="hidden" name="service" value="{{ $service }}">
        <div class="d-grid gap-3">
            <button name="priority" value="pwd_senior_pregnant" class="btn btn-warning btn-lg">PWD / Senior / Pregnant</button>
            <button name="priority" value="student" class="btn btn-primary btn-lg">Student</button>
            <button name="priority" value="parent" class="btn btn-info btn-lg">Parent</button>
        </div>
    </form>
</div>
</body>
</html>
