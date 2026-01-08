<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.6/dist/echo.iife.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <title>Your Ticket</title>

    <style>
        /* Loading Animation */
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 6px solid #dcdcdc;
            border-top: 6px solid #1d3c6e;
            /* Lourdes blue */
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }



        html,
        body {
            height: 100%;
            overflow: hidden;
            margin: 0;
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
            margin: 25px;
            height: calc(100vh - 130px);
            overflow: hidden;
            display: flex;
            border-radius: 20px;
            border: 3px solid #ffbad6;
            box-shadow: 0 4px 12px rgba(255, 120, 170, 0.3);
        }

        .left-column {
            flex: 0 0 35%;
            background: #ffe6f3;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 17px 0 0 17px;
            border-right: 2px solid #ffc1d9;
            overflow: hidden;
        }

        .left-column img {
            width: auto;
            height: 100%;
            object-fit: contain;
        }

        .right-column {
            flex: 0 0 65%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 80px;
            background: white;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-section img {
            width: 250px;
            height: auto;
        }

        .instruction-text {
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
            color: #c2185b;
            letter-spacing: 1px;
        }

        .number-label {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            color: #8c0f45;
        }

        .queue-code {
            font-size: 85px;
            font-weight: bold;
            font-style: italic;
            text-align: center;
            color: #c2185b;
            margin-bottom: 30px;
            letter-spacing: 3px;
            line-height: 1;
        }

        .footer-text {
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            color: #8c0f45;
            line-height: 1.5;
            letter-spacing: 0.5px;
        }

        /* Ensure proper receipt width */
        @media print {
            @page {
                margin: 0;
            }

            body {
                margin: 0 !important;
                padding: 12px 0 0 0 !important;
                width: 80mm;
            }

            .main-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>

<body>

    <!-- TOP HEADER -->
    <div class="header-bar">
        <div class="circle"></div>
        <h5 class="m-0 fw-bold">LOURDES COLLEGE, INC.</h5>
    </div>



    <div class="main-wrapper">

        <!-- LEFT COLUMN: STAFF IMAGE -->
        <div class="left-column">
            <img src="/images/louna.gif" alt="Staff">
        </div>

        <!-- RIGHT COLUMN: TICKET CONTENT -->
        <div class="right-column">

            <!-- LOGO -->
            <div class="logo-section">
                <img src="/images/Lourdes.png" alt="Lourdes College">
            </div>

            <div class="instruction-text">
                PLEASE TAKE YOUR QUEUE NUMBER.
            </div>

            <div class="number-label">
                YOUR NUMBER IS:
            </div>

            <div class="queue-code">
                {{ $ticket->code }}
            </div>

            <div class="footer-text">
                PLEASE PROCEED TO YOUR RESPECTIVE<br>
                WINDOW AS DISPLAYED. THANK YOU!
            </div>
            <div class="loading-spinner" id="loader"></div>
        </div>

    </div>

    <script>
        setTimeout(function() {
            window.location.href = "{{ route('kiosk.index') }}";
        }, 5000); // Redirect after 10 seconds
    </script>

    <script>
        // not used for now
        // let timeLeft = 5; // seconds
        // const element = document.getElementById("countdown");

        // const timer = setInterval(() => {
        //     element.textContent = timeLeft;
        //     timeLeft--;

        //     if (timeLeft < 0) {
        //         clearInterval(timer);
        //         element.textContent = "";
        //     }
        // }, 1000);
    </script>

</body>

</html>