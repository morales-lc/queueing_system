<!doctype html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.6/dist/echo.iife.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Select Counter</title>
</head>
<body class="p-4">
<div class="container">
    <h2>Choose Your Window</h2>
    <div class="row">
        <div class="col-md-6">
            <h4>Cashier</h4>
            <form method="post" action="{{ route('counter.claim') }}" class="d-grid gap-2">
                @csrf
                @foreach($cashier as $c)
                    <button class="btn {{ $c->claimed ? 'btn-secondary' : 'btn-primary' }}" {{ $c->claimed ? 'disabled' : '' }} name="counter_id" value="{{ $c->id }}">Cashier {{ $c->name }}</button>
                @endforeach
            </form>
        </div>
        <div class="col-md-6">
            <h4>Registrar</h4>
            <form method="post" action="{{ route('counter.claim') }}" class="d-grid gap-2">
                @csrf
                @foreach($registrar as $c)
                    <button class="btn {{ $c->claimed ? 'btn-secondary' : 'btn-success' }}" {{ $c->claimed ? 'disabled' : '' }} name="counter_id" value="{{ $c->id }}">Registrar {{ $c->name }}</button>
                @endforeach
            </form>
        </div>
    </div>
</div>

<script>
    // Refresh counter availability when other users claim/release
    document.addEventListener('DOMContentLoaded', () => {
        // Poll every 2 seconds to update counter availability
        setInterval(() => {
            location.reload();
        }, 2000);
    });
</script>
</body>
</html>
