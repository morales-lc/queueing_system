<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Select Registrar Program</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            overflow-x: hidden;
        }

        body {
            background-color: #ffedf5;
        }

        .header-bar {
            background: linear-gradient(90deg, #ff4fa0, #ff82c4);
            padding: 22px 20px 22px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
        }

        .header-bar .circle {
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

        .header-bar h5 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .main-wrapper {
            background: white;
            padding: 40px 60px;
            margin: 25px;
            border-radius: 20px;
            border: 3px solid #ffbad6;
            box-shadow: 0 4px 12px rgba(255, 120, 170, 0.3);
            min-height: calc(100vh - 160px);
            overflow: hidden;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-section img {
            width: 320px;
            max-width: 90vw;
            height: auto;
        }

        .instruction-text {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
            color: #c2185b;
        }

        .program-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .program-column {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .program-btn {
            width: 100%;
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
            border-radius: 15px;
            background: #ff78b6;
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
            transition: all 0.3s ease;
            text-align: center;
        }

        .program-btn:hover {
            background: #ff4fa0;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 60, 140, 0.5);
        }

        @media (max-width: 900px) {
            .program-grid {
                grid-template-columns: 1fr;
            }

            .main-wrapper {
                padding: 20px 10px;
                margin: 10px;
            }

            .instruction-text {
                font-size: 20px;
            }

            .program-btn {
                font-size: 20px;
                padding: 16px;
            }
        }

        @media (max-width: 600px) {
            .header-bar {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px 10px;
            }

            .header-bar .circle {
                margin-bottom: 10px;
                margin-right: 0;
            }

            .header-bar h5 {
                font-size: 1.1rem;
            }

            .main-wrapper {
                padding: 10px 8px;
                margin: 2px;
                border-radius: 10px;
            }

            .logo-section img {
                width: 200px;
            }

            .instruction-text {
                font-size: 16px;
            }

            .program-btn {
                font-size: 16px;
                padding: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="header-bar">
        <div class="circle"></div>
        <h5 class="m-0 fw-bold">LOURDES COLLEGE, INC.</h5>
        <div class="ms-auto">

        </div>
    </div>

    <div class="main-wrapper">
        @error('program')
            <div class="alert alert-danger fw-bold" role="alert"
                style="border:2px solid #ffbad6; box-shadow:0 3px 6px rgba(255,60,140,0.25)">
                <div class="d-flex align-items-center">
                    <span class="me-2">⚠️</span>
                    <span>{{ $message }}</span>
                </div>
            </div>
        @enderror

        <div class="logo-section">
            <img src="/images/Lourdes.png" alt="Lourdes College Logo">
        </div>

        <div class="instruction-text">
            PLEASE SELECT YOUR PROGRAM.
        </div>

        <div class="program-grid">
            <div class="program-column">
                @foreach($columnOnePrograms as $programKey)
                    <form method="POST" action="{{ route('kiosk.priority') }}">
                        @csrf
                        <input type="hidden" name="service" value="registrar">
                        <input type="hidden" name="program" value="{{ $programKey }}">
                        <button type="submit" class="btn program-btn">
                            {{ strtoupper($programs[$programKey]['label']) }}
                        </button>
                    </form>
                @endforeach
            </div>

            <div class="program-column">
                @foreach($columnTwoPrograms as $programKey)
                    <form method="POST" action="{{ route('kiosk.priority') }}">
                        @csrf
                        <input type="hidden" name="service" value="registrar">
                        <input type="hidden" name="program" value="{{ $programKey }}">
                        <button type="submit" class="btn program-btn">
                            {{ strtoupper($programs[$programKey]['label']) }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <a href="{{ route('kiosk.index') }}" class="btn btn-light fw-bold" style="border:2px solid #ffbad6;">
                ← Back
            </a>
        </div>
    </div>
</body>

</html>
