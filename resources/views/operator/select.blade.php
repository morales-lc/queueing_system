<!doctype html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
