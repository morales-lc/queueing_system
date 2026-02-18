<!doctype html>
<html>

<head>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Admin Session Control</title>

    <style>
        body {
            background: #ffedf5;
            margin: 0;
            height: 100vh;
        }

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

        .main-wrapper {
            display: grid;
            grid-template-columns: 70% 30%;
            height: calc(100vh - 80px);
        }

        .left-panel {
            padding: 24px;
            overflow-y: auto;
        }

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

        .card-stat {
            border: 0;
            border-radius: 12px;
        }

        .table thead th {
            white-space: nowrap;
        }
    </style>
</head>

<body>

    <div class="header-bar">
        <div class="d-flex align-items-center">
            <div class="circle"></div>
            <h5 class="fw-bold text-white mb-0">Admin - Session Control</h5>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.media.index') }}" target="_blank" rel="noopener noreferrer" class="btn btn-light fw-bold">Manage Monitor Content</a>
            <a href="{{ route('monitor.index') }}" class="btn btn-light fw-bold" target="_blank" rel="noopener noreferrer">Open Monitor</a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-danger fw-bold" type="submit">Logout</button>
            </form>
        </div>
    </div>

    <div class="main-wrapper">
        <div class="left-panel">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger mb-3">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Active Sessions</h4>
                <a href="{{ route('admin.sessions.index') }}" class="btn btn-dark btn-sm">Refresh</a>
            </div>

            <div class="table-responsive bg-white rounded shadow-sm">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Counter</th>
                            <th>IP</th>
                            <th>Last Activity</th>
                            <th>Device</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sessions as $session)
                            <tr>
                                <td class="fw-bold">{{ $session->username }}</td>
                                <td>{{ strtoupper($session->role) }}</td>
                                <td>
                                    @if ($session->counter_name)
                                        {{ ucfirst($session->counter_type) }} {{ $session->counter_name }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $session->ip_address ?? '—' }}</td>
                                <td title="{{ $session->last_activity_at }}">{{ $session->last_activity_human }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($session->user_agent ?? 'Unknown', 45) }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <form method="post" action="{{ route('admin.sessions.destroy', $session->session_id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit"
                                                onclick="return confirm('Terminate this session?')">
                                                End Session
                                            </button>
                                        </form>

                                        <form method="post"
                                            action="{{ route('admin.sessions.destroyUserSessions', $session->user_id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" type="submit"
                                                onclick="return confirm('Terminate ALL sessions for this user?')">
                                                End All
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No active sessions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="right-panel">
            <div class="panel-title">SESSION STATS</div>

            <div class="card card-stat shadow-sm mb-3">
                <div class="card-body">
                    <div class="text-muted">Total Active Sessions</div>
                    <div class="fs-3 fw-bold">{{ $stats['total_active_sessions'] }}</div>
                </div>
            </div>

            <div class="card card-stat shadow-sm mb-3">
                <div class="card-body">
                    <div class="text-muted">Users Online</div>
                    <div class="fs-3 fw-bold">{{ $stats['users_online'] }}</div>
                </div>
            </div>

            <div class="card card-stat shadow-sm mb-3">
                <div class="card-body">
                    <div class="text-muted">Cashier Sessions</div>
                    <div class="fs-4 fw-bold">{{ $stats['cashier_online'] }}</div>
                </div>
            </div>

            <div class="card card-stat shadow-sm mb-3">
                <div class="card-body">
                    <div class="text-muted">Registrar Sessions</div>
                    <div class="fs-4 fw-bold">{{ $stats['registrar_online'] }}</div>
                </div>
            </div>

            <div class="card card-stat shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Admin Sessions</div>
                    <div class="fs-4 fw-bold">{{ $stats['admin_online'] }}</div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
