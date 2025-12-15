<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Kiosk</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #ffedf5;
        }

        .header-bar {
            background: linear-gradient(90deg, #ff4fa0, #ff82c4);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
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
        }

        .main-wrapper {
            background: white;
            padding: 30px;
            margin-top: 25px;
            border-radius: 20px;
            border: 3px solid #ffbad6;
            box-shadow: 0 4px 12px rgba(255, 120, 170, 0.3);
            /* Help left avatar match right content height */
            display: block;
        }

        .left-image-box {
            background: #ffe6f3;
            padding: 0;
            border-radius: 15px;
            border: 2px solid #ffc1d9;
            box-shadow: 0 3px 6px rgba(255, 150, 180, 0.25);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .left-image-box img {
            max-height: 100%;
            max-width: 100%;
            height: auto;
            width: auto;
            object-fit: contain;
        }

        /* Make columns equal height so avatar aligns with registrar button area */
        .main-wrapper .row {
            display: flex;
            align-items: stretch;
        }

        .main-wrapper .col-md-5,
        .main-wrapper .col-md-7 {
            display: flex;
            flex-direction: column;
        }

        .main-wrapper .col-md-5>.left-image-box,
        .main-wrapper .col-md-7>.content-column {
            flex: 1 1 auto;
        }

        .service-btn {
            width: 100%;
            padding: 20px;
            font-size: 30px;
            font-weight: bold;
            border-radius: 15px;
            background: #ff78b6;
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
            transition: all 0.3s ease;
        }

        .service-btn:hover {
            background: #ff4fa0;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 60, 140, 0.5);
        }

        .divider {
            border-top: 2px solid #ffbad6;
            margin: 30px 0;
        }

        .instruction-text {
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            margin-top: 5px;
            margin-bottom: 20px;
            color: #c2185b;
        }

        ul {
            color: #8c0f45;
        }

        ul li {
            margin-bottom: 8px;
        }
    </style>
</head>

<body>

    <!-- TOP HEADER -->
    <div class="header-bar">
        <div class="circle"></div>
        <h5 class="m-0 fw-bold">LOURDES COLLEGE, INC.</h5>

    </div>

    <div class="container main-wrapper">

        <div class="row">

            <!-- LEFT IMAGE -->
            <div class="col-md-5">
                <div class="left-image-box">
                    <img src="/images/louna.gif" alt="Staff" class="img-fluid">
                </div>
            </div>

            <!-- RIGHT CONTENT -->
            <div class="col-md-7 ps-4 content-column">

                @error('printer')
                <div class="alert alert-danger fw-bold" role="alert" style="border:2px solid #ffbad6; box-shadow:0 3px 6px rgba(255,60,140,0.25)">
                    <div class="d-flex align-items-center">
                        <span class="me-2">⚠️</span>
                        <span>{{ $message }}</span>
                    </div>
                </div>
                @enderror

                <!-- LOGO -->
                <div class="text-center mb-1">
                    <img src="/images/Lourdes.png" width="280">
                </div>

                <div class="instruction-text">
                    PLEASE CLICK THE BUTTON OF THE RESPECTIVE PERSONNEL YOU NEED.
                </div>

                <!-- FIRST BULLETS -->
                <ul>
                    <li>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</li>
                    <li>Aenean tempus tortor non orci varius eleifend nec non urna.</li>
                    <li>Donec posuere quam ut ligula laoreet, a posuere risus mattis.</li>
                    <li>In mattis enim vel pharentra scelerisque.</li>
                </ul>

                <!-- CASHIER BUTTON -->
                <form method="POST" action="{{ route('kiosk.service') }}">
                    @csrf
                    <button name="service_type" value="cashier" class="btn service-btn mt-3">
                        CASHIER
                    </button>
                </form>

                <div class="divider"></div>

                <!-- SECOND BULLETS -->
                <ul>
                    <li>Vestibulum sed lectus sodales, pretium enim a, rutrum diam.</li>
                    <li>Proin quis orci ac erat condimentum vestibulum vel a felis.</li>
                    <li>Vestibulum eu arcu aliquam, ornare lacus in, semper lectus.</li>
                    <li>Cras sed orci cursus, vestibulum ex eu, sodales ligula.</li>
                </ul>

                <!-- REGISTRAR BUTTON -->
                <form method="POST" action="{{ route('kiosk.service') }}">
                    @csrf
                    <button name="service_type" value="registrar" class="btn service-btn mt-3">
                        REGISTRAR
                    </button>
                </form>

            </div>
        </div>

    </div>

    <script>
        // // Sync left avatar box height to right content (up to registrar button)
        // function syncAvatarHeight() {
        //     try {
        //         const content = document.querySelector('.content-column');
        //         const leftBox = document.querySelector('.left-image-box');
        //         if (!content || !leftBox) return;
        //         const h = content.offsetHeight;
        //         leftBox.style.height = h + 'px';
        //         const img = leftBox.querySelector('img');
        //         if (img) {
        //             img.style.height = '100%';
        //             img.style.width = 'auto';
        //             img.style.objectFit = 'contain';
        //         }
        //     } catch (e) {
        //         console.error(e);
        //     }
        // }

        // document.addEventListener('DOMContentLoaded', () => {
        //     syncAvatarHeight();
        //     window.addEventListener('resize', syncAvatarHeight);
        // });
    </script>

</body>

</html>