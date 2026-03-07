<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Restart Queue</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
	@vite(['resources/css/app.css', 'resources/js/app.js'])

	<style>
		body {
			min-height: 100vh;
			margin: 0;
			background: linear-gradient(135deg, #fff7ed 0%, #fee2e2 50%, #ffe4e6 100%);
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 24px;
		}

		.restart-card {
			width: 100%;
			max-width: 640px;
			border: 0;
			border-radius: 18px;
			box-shadow: 0 16px 40px rgba(15, 23, 42, 0.15);
			overflow: hidden;
		}

		.restart-header {
			background: linear-gradient(90deg, #dc2626 0%, #f97316 100%);
			color: #fff;
			padding: 18px 24px;
		}

		.count-pill {
			display: inline-block;
			background: #111827;
			color: #fff;
			border-radius: 999px;
			padding: 6px 12px;
			font-weight: 700;
			font-size: 0.9rem;
		}
	</style>
</head>

<body>
	<div class="card restart-card">
		<div class="restart-header">
			<h4 class="mb-0 fw-bold">Restart Queue For Today</h4>
		</div>

		<div class="card-body p-4 p-md-5">
			@if(session('status'))
				<div class="alert alert-success">{{ session('status') }}</div>
			@endif

			<div class="alert alert-warning">
				This will permanently delete all queue tickets created today for both cashier and registrar.
			</div>

			<p class="mb-2">Today's ticket count:</p>
			<p class="mb-4">
				<span class="count-pill">{{ $todayCount }} ticket(s)</span>
			</p>

			<p class="mb-3">Type <strong>RESTART</strong> to confirm.</p>

			<form method="post" action="{{ route('queue.restart.run') }}">
				@csrf

				<div class="mb-3">
					<input
						type="text"
						name="confirm_text"
						class="form-control @error('confirm_text') is-invalid @enderror"
						placeholder="Type RESTART"
						autocomplete="off"
						required
					>
					@error('confirm_text')
						<div class="invalid-feedback">Please type RESTART exactly to continue.</div>
					@enderror
				</div>

				<div class="d-flex gap-2">
					<button type="submit" class="btn btn-danger fw-bold">Restart Queue Now</button>
					<a href="{{ route('counter.index') }}" class="btn btn-outline-secondary fw-bold">Cancel</a>
				</div>
			</form>
		</div>
	</div>
</body>

</html>
