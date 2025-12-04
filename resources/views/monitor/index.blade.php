<!doctype html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>TV Monitor</title>
    <style>
        .counter-box { border: 2px solid #333; padding: 1rem; border-radius: .5rem; }
        .code { font-size: 2rem; font-weight: bold; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js"></script>
</head>
<body class="p-4">
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <h3 class="text-primary">Cashier</h3>
            <div class="row g-3">
                @foreach($cashierCounters as $c)
                    <div class="col-6">
                        <div class="counter-box" data-counter-id="{{ $c->id }}" data-counter-name="{{ $c->name }}">
                            <div>Window {{ $c->name }}</div>
                            <div class="code">{{ optional($nowServing['cashier'][$c->id])->code ?? '—' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-6">
            <h3 class="text-success">Registrar</h3>
            <div class="row g-3">
                @foreach($registrarCounters as $c)
                    <div class="col-6">
                        <div class="counter-box" data-counter-id="{{ $c->id }}" data-counter-name="{{ $c->name }}">
                            <div>Window {{ $c->name }}</div>
                            <div class="code">{{ optional($nowServing['registrar'][$c->id])->code ?? '—' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
function speak(text){
    const u = new SpeechSynthesisUtterance(text);
    window.speechSynthesis.speak(u);
}

let audioContext = null;

function initAudioContext() {
    if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
    // Resume context if suspended (browser autoplay policy)
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
        
        oscillator.frequency.value = 800; // Frequency in Hz
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    } catch (error) {
        console.error('Audio playback error:', error);
    }
}

// Echo is already initialized via resources/js/echo.js
document.addEventListener('DOMContentLoaded', () => {
    // Initialize audio context on first user interaction to satisfy browser autoplay policy
    document.body.addEventListener('click', initAudioContext, { once: true });
    
    // Listen for cashier tickets being served
    window.Echo.channel('queue.cashier').listen('.ticket.serving', (e) => {
        const code = e.ticket.code;
        const counterId = e.ticket.counter_id;
        // Find counter name from the page
        const counterElement = document.querySelector(`[data-counter-id="${counterId}"]`);
        const counterName = counterElement ? counterElement.dataset.counterName : counterId;
        
        // Play sound before reload
        playNotificationSound();
        
        // Delay TTS and reload slightly to allow sound to play
        setTimeout(() => {
            speak('Now serving ' + code + '. Please proceed to Cashier window ' + counterName);
            setTimeout(() => location.reload(), 100);
        }, 200);
    });

    // Listen for cashier tickets being done (to clear monitor)
    window.Echo.channel('queue.cashier').listen('.ticket.done', (e) => {
        setTimeout(() => location.reload(), 100);
    });

    // Listen for registrar tickets being served
    window.Echo.channel('queue.registrar').listen('.ticket.serving', (e) => {
        const code = e.ticket.code;
        const counterId = e.ticket.counter_id;
        // Find counter name from the page
        const counterElement = document.querySelector(`[data-counter-id="${counterId}"]`);
        const counterName = counterElement ? counterElement.dataset.counterName : counterId;
        
        // Play sound before reload
        playNotificationSound();
        
        // Delay TTS and reload slightly to allow sound to play
        setTimeout(() => {
            speak('Now serving ' + code + '. Please proceed to Registrar window ' + counterName);
            setTimeout(() => location.reload(), 100);
        }, 200);
    });

    // Listen for registrar tickets being done (to clear monitor)
    window.Echo.channel('queue.registrar').listen('.ticket.done', (e) => {
        setTimeout(() => location.reload(), 100);
    });
});
</script>
</body>
</html>
