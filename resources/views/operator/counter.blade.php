<!doctype html>
<html>

<head>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.6/dist/echo.iife.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Counter</title>

    <style>
        body {
            background: #ffedf5;
            margin: 0;
            height: 100vh;
        }

        /* HEADER */
        .header-bar {
            background: linear-gradient(90deg, #ff4fa0, #ff82c4);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
        }

        .circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #fff;
            background-image: url('/images/LCCDO.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            margin-right: 20px;
            border: 3px solid #ffbad6;
        }

        /* MAIN GRID */
        .main-wrapper {
            display: grid;
            grid-template-columns: 70% 30%;
            height: calc(100vh - 80px);
        }

        /* LEFT PANEL */
        .left-panel {
            padding: 40px;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .cashier-title {
            font-size: 36px;
            font-weight: bold;
        }

        .call-again-btn {
            position: absolute;
            top: 40px;
            right: 40px;
            left: auto;
            transform: none;
            background: #2d2d2d;
            color: #fff;
            border: none;
            padding: 10px 28px;
            border-radius: 6px;
            font-weight: bold;
        }

        /* CENTER SERVING AREA */
        .serving-center {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .serving-label {
            letter-spacing: 2px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .serving-code {
            font-size: 96px;
            font-weight: 900;
        }

        .bottom-actions {
            position: absolute;
            bottom: 60px;
            width: 100%;
            display: flex;
            justify-content: center;
            gap: 80px;
        }

        .bottom-actions button {
            padding: 14px 40px;
            font-weight: bold;
        }

        /* RIGHT PANEL */
        .right-panel {
            background: #f1f1f1;
            border-left: 2px solid #ccc;
            padding: 20px;
            overflow-y: auto;
        }

        .panel-title {
            background: #d0d0d0;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="header-bar">
        <div class="d-flex align-items-center">
            <div class="circle"></div>
            <h5 class="fw-bold text-white mb-0">
                {{ ucfirst($counter->type) }} {{ $counter->name }}
            </h5>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('media.index') }}" class="btn btn-light fw-bold">Manage Monitor</a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-light fw-bold">Logout</button>
            </form>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main-wrapper">

        <!-- LEFT -->
        <div class="left-panel">

            <!-- CALL AGAIN for currently serving: only show if nowServing exists -->
            @if($nowServing)
            <form method="post" action="{{ route('counter.callAgain', [$counter->id, $nowServing->id]) }}" style="display:inline">
                @csrf
                <button type="submit" class="call-again-btn">CALL AGAIN</button>
            </form>
            @endif

            <!-- CENTERED SERVING -->
            <div class="serving-center">
                <div class="serving-label">CURRENTLY SERVING:</div>

                @if($nowServing)
                <div class="serving-code">{{ $nowServing->code }}</div>
                @else
                <div class="serving-code">—</div>
                @endif
            </div>

            <div class="bottom-actions">
                @if($nowServing)
                <form method="post" action="{{ route('counter.hold', [$counter->id, $nowServing->id]) }}">
                    @csrf
                    <button class="btn btn-dark">ON-HOLD</button>
                </form>
                @endif

                <form method="post" action="{{ route('counter.next', $counter->id) }}">
                    @csrf
                    <button class="btn btn-dark">NEXT</button>
                </form>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="right-panel">

            <div class="panel-title">QUEUE</div>
            <ul class="list-group mb-4">
                @forelse($queue as $t)
                <li class="list-group-item text-center fw-bold">
                    {{ $t->code }}
                </li>
                @empty
                <li class="list-group-item text-center">No tickets.</li>
                @endforelse
            </ul>

            <div class="panel-title">ON-HOLD</div>
            <ul class="list-group">
                @forelse($onHold as $t)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="fw-bold">{{ $t->code }}</span>

                    <div class="btn-group">
                        <form method="post" action="{{ route('counter.callAgain', [$counter->id, $t->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">Call Again</button>
                        </form>
                        <form method="post" action="{{ route('counter.removeHold', [$counter->id, $t->id]) }}">
                            @method('DELETE')
                            @csrf
                            <button class="btn btn-outline-danger btn-sm">✕</button>
                        </form>
                    </div>
                </li>
                @empty
                <li class="list-group-item text-center">No on-hold tickets.</li>
                @endforelse
            </ul>

        </div>
    </div>

    <!-- JS / ECHO  -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const counterId = {{ $counter->id }};
            window.Echo.channel('queue.{{ $counter->type }}')
                .listen('.ticket.created', () => location.reload())
                .listen('.ticket.serving', () => location.reload())
                .listen('.ticket.on_hold', () => location.reload())
                .listen('.ticket.done', () => location.reload());
        });
    </script>

</body>

</html>