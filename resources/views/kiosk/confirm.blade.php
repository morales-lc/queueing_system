<!doctype html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.6/dist/echo.iife.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <title>Confirm</title>
</head>
<body class="p-4">
<div class="container">
    <h2>Confirm Selection</h2>
    @if($errors->has('printer'))
        <div class="alert alert-danger fw-bold" role="alert" style="border:2px solid #ffbad6; box-shadow:0 3px 6px rgba(255,60,140,0.25)">
            <div class="d-flex align-items-center">
                <span class="me-2">⚠️</span>
                <span>{{ $errors->first('printer') }}</span>
            </div>
        </div>
    @endif
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
