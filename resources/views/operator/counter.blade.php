<!doctype html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.6/dist/echo.iife.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Counter</title>
    <style>
        body {
            background-color: #ffedf5;
            margin: 0;
            padding: 0;
        }

        .header-bar {
            background: linear-gradient(90deg, #ff4fa0, #ff82c4);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
        }

        .header-bar .left-section {
            display: flex;
            align-items: center;
        }

        .header-bar .circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #fff;
            margin-right: 20px;
            border: 3px solid #ffbad6;
        }

        .header-bar h5 {
            color: #fff;
            margin: 0;
        }

        .header-bar .logout-btn {
            background: #fff;
            color: #ff4fa0;
            border: 2px solid #ffbad6;
            padding: 8px 24px;
            font-weight: bold;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .header-bar .logout-btn:hover {
            background: #ffe6f3;
            border-color: #ff78b6;
            transform: translateY(-2px);
        }

        .main-content {
            padding: 20px;
        }
    </style>
</head>
<body>

<!-- TOP HEADER -->
<div class="header-bar">
    <div class="left-section">
        <div class="circle"></div>
        <h5 class="fw-bold">{{ ucfirst($counter->type) }} {{ $counter->name }}</h5>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('media.index') }}" class="btn logout-btn">
            Manage Media
        </a>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button class="btn logout-btn">Logout</button>
        </form>
    </div>
</div>

<div class="container main-content">

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
                        <button type="button" class="btn btn-success btn-sm" data-code="{{ $t->code }}" data-url="{{ route('counter.callAgain', [$counter->id, $t->id]) }}" onclick="callAgainWithTTS(this.dataset.code, this.dataset.url)">Call Again</button>
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

        // Removed auto-release on tab close to avoid 419 issues

        // Echo is already initialized via resources/js/echo.js
        document.addEventListener('DOMContentLoaded', () => {
            const counterId = {{ $counter->id }};
            function addTicketToQueue(ticket) {
                try {
                    const list = document.querySelector('.list-group');
                    if (!list || !ticket) return;
                    const code = ticket.code;
                    const priority = ticket.priority?.replace('_', ' ') ?? '';
                    const exists = Array.from(list.querySelectorAll('li span'))
                        .some(span => span.textContent.includes(code));
                    if (exists) return;

                    const items = list.querySelectorAll('li.list-group-item');
                    const nonEmptyItems = Array.from(items).filter(li => !li.textContent.includes('No tickets'));
                    if (nonEmptyItems.length >= 5) return; // keep next 5

                    // Remove "No tickets." placeholder if present
                    const emptyItem = Array.from(items).find(li => li.textContent.includes('No tickets.'));
                    if (emptyItem) emptyItem.remove();

                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center';
                    const span = document.createElement('span');
                    span.textContent = `${code} — ${priority.charAt(0).toUpperCase() + priority.slice(1)}`;
                    li.appendChild(span);
                    list.appendChild(li);
                } catch (err) {
                    console.error(err);
                }
            }
            // Listen for new tickets created for this service type
            window.Echo.channel('queue.{{ $counter->type }}').listen('.ticket.created', (e) => {
                // Add new ticket to queue list without full page reload
                addTicketToQueue(e.ticket);
            });

            // Listen for tickets being served
            window.Echo.channel('queue.{{ $counter->type }}').listen('.ticket.serving', (e) => {
                if (e.ticket.counter_id === counterId) {
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
