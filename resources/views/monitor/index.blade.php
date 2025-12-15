<!doctype html>
<html>

<head>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>TV Monitor</title>

    <style>
        html,
        body {
            height: 100%;
            overflow: hidden !important;

            background: #ffedf5 !important;
            /* soft light pink */
        }

        .container-fluid {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 0.5rem;
            color: #c2185b;
            /* muted dark pink */
        }

        .now-serving {
            background: #ff78b6;
            color: white;
            text-align: center;
            padding: .4rem;
            font-size: 1.3rem;
            font-weight: 700;
            border-radius: 10px;
        }

        .ticket-box {
            background: #ffe4ef;
            /* very light pink */
            border: 2px solid #ffc1d9;
            border-radius: 15px;
            display: flex;
            align-items: center;
            padding: 1rem;
            height: 130px;
            margin-bottom: 10px;
            box-shadow: 0 3px 6px rgba(255, 150, 180, 0.25);
            transition: all 0.3s ease;
        }

        .ticket-box.unavailable {
            background: #e0e0e0;
            border-color: #bdbdbd;
            opacity: 0.5;
        }

        .ticket-box.unavailable .ticket-number,
        .ticket-box.unavailable .ticket-code {
            color: #9e9e9e;
        }

        /* Blink highlight for latest updated code text */
        @keyframes codeBlink {
            0%, 100% {
                color: #8c0f45;
                text-shadow: none;
                background: transparent;
            }
            50% {
                color: #ffffff;
                text-shadow: 0 0 8px rgba(255, 60, 140, 0.9);
                background: linear-gradient(90deg, #ff4fa0 0%, #ff82c4 100%);
                -webkit-background-clip: text;
                background-clip: text;
                -webkit-text-fill-color: transparent;
            }
        }
        .ticket-code.blink {
            animation: codeBlink 1.0s ease-in-out 20;
        }

        .ticket-number {
            flex: 0 0 25%;
            font-size: 3.5rem;
            font-weight: 900;
            text-align: center;
            color: #d81b60;
        }

        .ticket-code {
            flex: 1;
            font-size: 2rem;
            font-weight: 700;
            padding-left: 1rem;
            color: #8c0f45;
        }

        /* Right panel screen */
        .big-screen {
            border-radius: 20px;
            border: 3px solid #ffbad6;
            height: 100%;
            width: 100%;
            background: #ffe6f3;
            background-size: cover;
            box-shadow: 0 4px 12px rgba(255, 120, 170, 0.3);
        }

        /* Modern marquee */
        .custom-marquee {
            font-size: 2rem;
            font-weight: 900;
            padding: 10px 0;
            height: 50px;
            color: #fff;
            background: linear-gradient(90deg, #ff4fa0, #ff82c4);
            border-radius: 12px;
            letter-spacing: 1.5px;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
        }

        /* Top row uses only needed height */
        .top-row {
            flex: 0 0 auto;
        }

        /* Bottom row fills all remaining space */
        .bottom-row {
            flex: 1;
            display: flex;
        }

        /* Marquee stretches to fill the whole bottom */
        .bottom-row marquee {
            flex: 1;
            height: 70% !important;
            display: flex;
            align-items: center;
            /* vertically center text */
        }
    </style>


    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.6/dist/echo.iife.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

</head>

<body class="p-3">
    <div class="container-fluid">

        <div class="row">
            <!-- CASHIER COLUMN -->
            <div class="col-3 p-0">
                <div class="section-title">CASHIER</div>
                <div class="now-serving">NOW SERVING</div>

                @foreach($cashierCounters as $c)
                <div class="ticket-box {{ $c->claimed ? '' : 'unavailable' }}" data-counter-id="{{ $c->id }}" data-counter-name="{{ $c->name }}">
                    <div class="ticket-number">{{ $c->name }}</div>
                    <div class="ticket-code">{{ optional($nowServing['cashier'][$c->id])->code ?? '—' }}</div>
                </div>
                @endforeach
            </div>

            <!-- REGISTRAR COLUMN -->
            <div class="col-3 p-0">
                <div class="section-title">REGISTRAR</div>
                <div class="now-serving">NOW SERVING</div>

                @foreach($registrarCounters as $c)
                <div class="ticket-box {{ $c->claimed ? '' : 'unavailable' }}" data-counter-id="{{ $c->id }}" data-counter-name="{{ $c->name }}">
                    <div class="ticket-number">{{ $c->name }}</div>
                    <div class="ticket-code">{{ optional($nowServing['registrar'][$c->id])->code ?? '—' }}</div>
                </div>
                @endforeach
            </div>

            <div class="col-6">
                <div class="big-screen" id="mediaPanel">
                    @include('monitor._media')
                </div>
            </div>
        </div>

        <div class="row bottom-row">
            <marquee class="custom-marquee mt-3" direction="left" loop="20">
                {{ $marqueeText }}
            </marquee>
        </div>

    </div>



    <script>
        function speak(text) {
            const u = new SpeechSynthesisUtterance(text);
            window.speechSynthesis.speak(u);
        }

        let audioContext = null;

        function initAudioContext() {
            if (!audioContext) {
                audioContext = new(window.AudioContext || window.webkitAudioContext)();
            }
            if (audioContext.state === 'suspended') {
                audioContext.resume();
            }
        }

        function playNotificationSound() {
            try {
                initAudioContext();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
            } catch (error) {
                console.error(error);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.body.addEventListener('click', initAudioContext, {
                once: true
            });

            function updateCounterTicket(counterId, code) {
                try {
                    const box = document.querySelector(`.ticket-box[data-counter-id="${counterId}"]`);
                    if (!box) return;
                    const codeEl = box.querySelector('.ticket-code');
                    if (codeEl) {
                        codeEl.textContent = code ?? '—';
                        // Blink the text to indicate latest update
                        codeEl.classList.remove('blink');
                        void codeEl.offsetWidth; // reflow to restart animation
                        codeEl.classList.add('blink');
                    }
                } catch (err) {
                    console.error(err);
                }
            }

            // CASHIER
            window.Echo.channel('queue.cashier').listen('.ticket.serving', (e) => {
                playNotificationSound();
                setTimeout(() => {
                    speak('Now serving ' + e.ticket.code + '. Please proceed to Cashier window ' + e.ticket.counter_id);
                    updateCounterTicket(e.ticket.counter_id, e.ticket.code);
                }, 200);
            });

            window.Echo.channel('queue.cashier').listen('.ticket.done', (e) => {
                // Clear the display for the counter that finished
                updateCounterTicket(e.ticket.counter_id, '—');
            });

            // REGISTRAR
            window.Echo.channel('queue.registrar').listen('.ticket.serving', (e) => {
                playNotificationSound();
                setTimeout(() => {
                    speak('Now serving ' + e.ticket.code + '. Please proceed to Registrar window ' + e.ticket.counter_id);
                    updateCounterTicket(e.ticket.counter_id, e.ticket.code);
                }, 200);
            });

            window.Echo.channel('queue.registrar').listen('.ticket.done', (e) => {
                updateCounterTicket(e.ticket.counter_id, '—');
            });

            // Listen for counter availability changes
            window.Echo.channel('counter.status').listen('.counter.status.changed', (e) => {
                const box = document.querySelector(`.ticket-box[data-counter-id="${e.counter_id}"]`);
                if (!box) return;
                
                if (e.status === 'available') {
                    box.classList.remove('unavailable');
                } else if (e.status === 'unavailable') {
                    box.classList.add('unavailable');
                    // Clear the ticket display when counter becomes unavailable
                    const codeEl = box.querySelector('.ticket-code');
                    if (codeEl) {
                        codeEl.textContent = '—';
                        codeEl.classList.remove('blink');
                    }
                }
            });
            
            // Listen for media changes and refresh the right panel
            window.Echo.channel('monitor.media').listen('.media.updated', async (e) => {
                try {
                    const resp = await fetch('{{ route('monitor.media') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!resp.ok) return;
                    const html = await resp.text();
                    const panel = document.getElementById('mediaPanel');
                    if (panel) {
                        panel.innerHTML = html;
                        initSlideshow();
                    }
                } catch (_) {}
            });
        });

        // Media slideshow functionality
        function initSlideshow() {
            const slides = Array.from(document.querySelectorAll('#mediaPanel .media-slide'));
            if (slides.length === 0) return;
            let current = slides.findIndex(s => s.classList.contains('active'));
            if (current < 0) current = 0;
            let timer = null;

            function clearTimer() { if (timer) { clearTimeout(timer); timer = null; } }

            function show(index) {
                clearTimer();
                slides.forEach((s, i) => {
                    s.style.display = i === index ? 'block' : 'none';
                    s.classList.toggle('active', i === index);
                });
                const el = slides[index];
                if (el.tagName === 'VIDEO') {
                    try { el.currentTime = 0; el.play(); } catch (_) {}
                    el.onended = () => advance();
                } else {
                    // time for next
                    timer = setTimeout(() => advance(), 3000);
                }
            }

            function advance() {
                current = (current + 1) % slides.length;
                show(current);
            }

            show(current);
        }

        // Initialize on first load
        initSlideshow();
    </script>

</body>

</html>