<!doctype html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.6/dist/echo.iife.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Counter</title>
</head>
<body class="p-4">
<div class="container">
    <div class="d-flex justify-content-between align-items-center">
        <h3>{{ ucfirst($counter->type) }} {{ $counter->name }}</h3>
        <form method="post" action="{{ route('counter.release') }}">
            @csrf
            <input type="hidden" name="counter_id" value="{{ $counter->id }}">
            <button class="btn btn-danger">Exit</button>
        </form>
    </div>

    <div class="mt-4 p-4 bg-primary text-white rounded text-center">
        <h4 class="mb-2">Now Serving</h4>
        @if($nowServing)
            <div class="display-3 fw-bold">{{ $nowServing->code }}</div>
            <p class="mb-0">{{ ucfirst(str_replace('_', ' ', $nowServing->priority)) }}</p>
            <form method="post" action="{{ route('counter.hold', [$counter->id, $nowServing->id]) }}" class="mt-3">
                @csrf
                <button class="btn btn-warning">Put On Hold</button>
            </form>
        @else
            <div class="display-6">—</div>
            <p class="mb-0">No ticket being served</p>
        @endif
    </div>

    <div class="mt-4">
        <h5>Queue (next 5)</h5>
        <ul class="list-group">
            @forelse($queue as $t)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>{{ $t->code }} — {{ ucfirst($t->priority) }}</span>
                </li>
            @empty
                <li class="list-group-item">No tickets.</li>
            @endforelse
        </ul>

        <form method="post" action="{{ route('counter.next', $counter->id) }}" class="mt-3">
            @csrf
            <button class="btn btn-primary btn-lg">Next</button>
        </form>
    </div>

    <div class="mt-4">
        <h5>On Hold</h5>
        <ul class="list-group">
            @forelse($onHold as $t)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>{{ $t->code }} — holds: {{ $t->hold_count }}</span>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success btn-sm" onclick="callAgainWithTTS('{{ $t->code }}', '{{ route('counter.callAgain', [$counter->id, $t->id]) }}')">Call Again</button>
                        <form method="post" action="{{ route('counter.removeHold', [$counter->id, $t->id]) }}" style="display:inline;">
                            @method('DELETE')
                            @csrf
                            <button class="btn btn-outline-danger btn-sm">Remove</button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="list-group-item">No on-hold tickets.</li>
            @endforelse
        </ul>
    </div>

    <script>
        function speak(text){
            const u = new SpeechSynthesisUtterance(text);
            window.speechSynthesis.speak(u);
        }

        // Call Again with immediate TTS announcement
        function callAgainWithTTS(code, url) {
            // Announce immediately with counter info
            const counterType = '{{ ucfirst($counter->type) }}';
            const counterName = '{{ $counter->name }}';
            speak('Now serving ' + code + '. Please proceed to ' + counterType + ' window ' + counterName);
            
            // Submit the form via POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            document.body.appendChild(form);
            form.submit();
        }

        // Auto-release counter when browser/tab closes
        window.addEventListener('beforeunload', (e) => {
            // Use sendBeacon for reliable async request during unload
            const formData = new FormData();
            formData.append('counter_id', '{{ $counter->id }}');
            formData.append('_token', '{{ csrf_token() }}');
            navigator.sendBeacon('{{ route("counter.release") }}', formData);
        });

        // Echo is already initialized via resources/js/echo.js
        document.addEventListener('DOMContentLoaded', () => {
            // Listen for new tickets created for this service type
            window.Echo.channel('queue.{{ $counter->type }}').listen('.ticket.created', (e) => {
                // Reload to show new ticket in queue
                location.reload();
            });

            // Listen for tickets being served
            window.Echo.channel('queue.{{ $counter->type }}').listen('.ticket.serving', (e) => {
                if (e.ticket.counter_id === {{ $counter->id }}) {
                    const counterType = '{{ ucfirst($counter->type) }}';
                    const counterName = '{{ $counter->name }}';
                    speak('Now serving ' + e.ticket.code + '. Please proceed to ' + counterType + ' window ' + counterName);
                }
                // Reload to update queue list
                location.reload();
            });

            // Listen for tickets being put on hold
            window.Echo.channel('queue.{{ $counter->type }}').listen('.ticket.on_hold', (e) => {
                location.reload();
            });

            // Listen for tickets being marked done
            window.Echo.channel('queue.{{ $counter->type }}').listen('.ticket.done', (e) => {
                location.reload();
            });
        });
    </script>
</div>
</body>
</html>
