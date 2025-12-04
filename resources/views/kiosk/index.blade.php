<!doctype html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Kiosk</title>
</head>
<body class="p-4">
<div class="container">
    <h2>Select Service</h2>
    <form method="post" action="{{ route('kiosk.service') }}" class="mt-3">
        @csrf
        <div class="btn-group" role="group">
            <button name="service_type" value="cashier" class="btn btn-primary btn-lg">Cashier</button>
            <button name="service_type" value="registrar" class="btn btn-success btn-lg">Registrar</button>
        </div>
    </form>
</div>
</body>
</html>
