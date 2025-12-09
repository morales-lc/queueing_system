@if($mediaItems->count() > 0)
    @foreach($mediaItems as $index => $media)
        @php
            $ext = pathinfo($media->filename, PATHINFO_EXTENSION);
        @endphp
        @if($media->type === 'video')
            <video class="media-slide {{ $index === 0 ? 'active' : '' }}" autoplay muted playsinline style="width:100%; height:100%; object-fit:contain; border-radius: 20px; display: {{ $index === 0 ? 'block' : 'none' }};">
                <source src="{{ asset('storage/' . $media->path) }}" type="video/{{ $ext }}">
            </video>
        @elseif($media->type === 'gif')
            <img class="media-slide {{ $index === 0 ? 'active' : '' }}" src="{{ asset('storage/' . $media->path) }}" alt="{{ $media->original_filename }}" style="width:100%; height:100%; object-fit:contain; border-radius: 20px; display: {{ $index === 0 ? 'block' : 'none' }};" />
        @else
            <img class="media-slide {{ $index === 0 ? 'active' : '' }}" src="{{ asset('storage/' . $media->path) }}" alt="{{ $media->original_filename }}" style="width:100%; height:100%; object-fit:contain; border-radius:  20px; display: {{ $index === 0 ? 'block' : 'none' }};" />
        @endif
    @endforeach
@else
    <video id="promoVideo" autoplay muted loop playsinline style="width:100%; height:100%; object-fit:contain; border-radius: 20px;">
        <source src="{{ asset('videos/promo.mp4') }}" type="video/mp4" />
    </video>
@endif
