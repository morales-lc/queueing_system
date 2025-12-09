<!doctype html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Select Counter</title>
</head>
<body class="p-4">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Choose Your {{ ucfirst($userRole) }} Window</h2>
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button class="btn btn-outline-danger">Logout</button>
        </form>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Available {{ ucfirst($userRole) }} Counters</h4>
                    <form method="post" action="{{ route('counter.claim') }}" class="d-grid gap-3">
                        @csrf
                        @foreach($counters as $c)
                            <button class="btn btn-lg {{ $c->claimed ? 'btn-secondary' : 'btn-primary' }}" 
                                    {{ $c->claimed ? 'disabled' : '' }} 
                                    name="counter_id" 
                                    value="{{ $c->id }}">
                                {{ ucfirst($userRole) }} {{ $c->name }}
                                @if($c->claimed)
                                    <span class="badge bg-danger ms-2">In Use</span>
                                @else
                                    <span class="badge bg-success ms-2">Available</span>
                                @endif
                            </button>
                        @endforeach
                    </form>
                </div>
            </div>
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
