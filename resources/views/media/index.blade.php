<!doctype html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <title>Manage Monitor Media</title>
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

        .header-bar .btn {
            background: #fff;
            color: #ff4fa0;
            border: 2px solid #ffbad6;
            padding: 8px 24px;
            font-weight: bold;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .header-bar .btn:hover {
            background: #ffe6f3;
            border-color: #ff78b6;
            transform: translateY(-2px);
        }

        .main-content {
            padding: 30px;
        }

        .media-card {
            background: white;
            border: 2px solid #ffc1d9;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 6px rgba(255, 150, 180, 0.25);
            cursor: move;
            transition: all 0.3s ease;
        }

        .media-card:hover {
            box-shadow: 0 6px 12px rgba(255, 150, 180, 0.4);
        }

        .media-card.inactive {
            opacity: 0.5;
            background: #f5f5f5;
        }

        .media-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px solid #ffbad6;
        }

        .upload-box {
            background: white;
            border: 3px dashed #ffc1d9;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .upload-box:hover {
            border-color: #ff78b6;
            background: #ffe6f3;
        }

        .drag-handle {
            cursor: grab;
            color: #ff78b6;
        }

        .drag-handle:active {
            cursor: grabbing;
        }
    </style>
</head>
<body>

<!-- TOP HEADER -->
<div class="header-bar">
    <div class="left-section">
        <div class="circle"></div>
        <h5 class="fw-bold">Manage Monitor Media</h5>
    </div>
    <a href="{{ auth()->user()->counter_id ? route('counter.show', auth()->user()->counter_id) : route('counter.index') }}" class="btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16" style="margin-right: 5px;">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
        </svg>
        Back to Counter
    </a>
</div>

<div class="container main-content">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Upload Section -->
    <div class="upload-box">
        <h4 class="mb-3" style="color: #c2185b;">Upload New Media</h4>
        <p class="text-muted">Supported formats: JPG, PNG, GIF, MP4, AVI, MOV, WEBM (Max: 100MB)</p>
        <form method="POST" action="{{ route('media.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <input type="file" class="form-control" name="file" accept=".jpg,.jpeg,.png,.gif,.mp4,.avi,.mov,.webm" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="background: #ff78b6; border: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cloud-upload" viewBox="0 0 16 16" style="margin-right: 5px;">
                    <path fill-rule="evenodd" d="M4.406 1.342A5.53 5.53 0 0 1 8 0c2.69 0 4.923 2 5.166 4.579C14.758 4.804 16 6.137 16 7.773 16 9.569 14.502 11 12.687 11H10a.5.5 0 0 1 0-1h2.688C13.979 10 15 8.988 15 7.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 2.825 10.328 1 8 1a4.53 4.53 0 0 0-2.941 1.1c-.757.652-1.153 1.438-1.153 2.055v.448l-.445.049C2.064 4.805 1 5.952 1 7.318 1 8.785 2.23 10 3.781 10H6a.5.5 0 0 1 0 1H3.781C1.708 11 0 9.366 0 7.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383"/>
                    <path fill-rule="evenodd" d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708z"/>
                </svg>
                Upload Media
            </button>
        </form>
    </div>

    <!-- Media List -->
    <h4 class="mb-3" style="color: #c2185b;">Media Library</h4>
    <p class="text-muted">Drag and drop to reorder. Click to toggle active/inactive status.</p>

    <div id="mediaList">
            @forelse($media as $item)
            <div class="media-card {{ $item->is_active ? '' : 'inactive' }}" data-id="{{ $item->id }}">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="drag-handle fs-3">⋮⋮</span>
                    </div>
                    <div class="col-auto">
                        @if($item->type === 'video')
                            <video class="media-preview" muted>
                                <source src="{{ asset('storage/' . $item->path) }}" type="video/{{ pathinfo($item->filename, PATHINFO_EXTENSION) }}">
                            </video>
                        @else
                            <img src="{{ asset('storage/' . $item->path) }}" class="media-preview" alt="{{ $item->original_filename }}">
                        @endif
                    </div>
                    <div class="col">
                        <h5 class="mb-1">{{ $item->original_filename }}</h5>
                        <p class="mb-1 text-muted">
                            <strong>Type:</strong> {{ ucfirst($item->type) }} | 
                            <strong>Status:</strong> 
                            <span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $item->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </p>
                        <input type="hidden" value="{{ $item->order }}" class="order-input">
                    </div>
                    <div class="col-auto">
                        <form method="POST" action="{{ route('media.toggleActive', $item->id) }}" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                {{ $item->is_active ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('media.destroy', $item->id) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this media?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-5">
                <p class="text-muted">No media files uploaded yet. Upload your first media file above!</p>
            </div>
            @endforelse
    </div>

    @if($media->count() > 0)
    <form method="POST" action="{{ route('media.updateOrder') }}" id="orderForm">
        @csrf
        <input type="hidden" name="orders_json" id="ordersJson">
        <div class="text-center mt-4">
            <button type="submit" class="btn btn-lg" style="background: #ff78b6; color: white; border: none;">
                Save Order
            </button>
        </div>
    </form>
    @endif
</div>

<script>
    // Initialize Sortable
    const mediaList = document.getElementById('mediaList');
    if (mediaList && mediaList.children.length > 0) {
        new Sortable(mediaList, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function(evt) {
                const cards = mediaList.querySelectorAll('.media-card');
                const orders = {};
                cards.forEach((card, index) => {
                    const id = card.getAttribute('data-id');
                    orders[id] = index;
                    const input = card.querySelector('.order-input');
                    if (input) input.value = index;
                });
                const ordersJsonEl = document.getElementById('ordersJson');
                if (ordersJsonEl) ordersJsonEl.value = JSON.stringify(orders);
            }
        });
    }

    // Prepare initial orders JSON
    (function prepareInitialOrders(){
        const cards = mediaList ? mediaList.querySelectorAll('.media-card') : [];
        const orders = {};
        cards.forEach((card, index) => {
            const id = card.getAttribute('data-id');
            orders[id] = index;
        });
        const ordersJsonEl = document.getElementById('ordersJson');
        if (ordersJsonEl) ordersJsonEl.value = JSON.stringify(orders);
    })();

    // After any action, broadcast is handled server-side; optionally refresh here on return
</script>

</body>
</html>
