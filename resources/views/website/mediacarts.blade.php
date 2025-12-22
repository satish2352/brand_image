<style>
.media-card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: 0.3s;
}
.media-card:hover { transform: translateY(-5px); }
.media-card img {
    width: 100%;
    height: 160px;
    object-fit: cover;
}
.media-body { padding: 12px; }
.media-body h6 { margin: 0; font-weight: 600; }
.media-body p { margin: 5px 0; color: #28a745; font-weight: bold; }

.media-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.qty-box {
    display: flex;
    align-items: center;
}
.qty-box button {
    width: 30px;
    height: 30px;
    padding: 0;
}
.qty-box input {
    width: 40px;
    text-align: center;
    border: 1px solid #ccc;
    margin: 0 5px;
}
</style>

@php
    $cart = session()->get('cart', []);
@endphp

<div class="container my-5">
    <div class="row">

        @forelse($mediaList as $media)

            @php
                $inCart = isset($cart[$media->id]);
                $qty = $inCart ? $cart[$media->id]['qty'] : 1;
            @endphp

            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="media-card">

                 

                           <img src="{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}"
                             class="img-fluid rounded"
                             style="height:150px; width:100%; object-fit:cover;">

                    <div class="media-body">

                        <h6>{{ $media->media_title ?? $media->category_name }}</h6>

                        <small class="text-muted d-block">
                            {{ $media->area_name }}, {{ $media->city_name }}
                        </small>

                        <p class="mt-2">
                            ₹ {{ number_format($media->price, 2) }}
                        </p>

                        <div class="media-actions mt-2">

                            <a href="{{ url('media/view-details/' . encrypt($media->id)) }}"
                               class="btn btn-sm btn-primary">
                                View
                            </a>

                            {{-- ONLY FOR ALLOWED CATEGORIES --}}
                            @if(
                                $media->category_name === 'Hoardings/Billboards' ||
                                $media->category_name === 'Digital Wall painting/Wall Painting'
                            )

                                {{-- IF NOT IN CART --}}
                                {{-- @if(!$inCart) --}}
                                    <a href="{{ route('cart.add', encrypt($media->id)) }}"
                                       class="btn btn-sm btn-success">
                                        Add to Cart
                                    </a>
                                {{-- @else --}}
                                    {{-- QUANTITY CONTROLS --}}
                                    {{-- <form method="POST"
                                          action="{{ route('cart.update') }}"
                                          class="qty-box">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $media->id }}">

                                        <button type="submit"
                                                name="qty"
                                                value="{{ $qty - 1 }}"
                                                class="btn btn-sm btn-secondary">−</button>

                                        <input type="text" value="{{ $qty }}" readonly>

                                        <button type="submit"
                                                name="qty"
                                                value="{{ $qty + 1 }}"
                                                class="btn btn-sm btn-secondary">+</button>
                                    </form> --}}
                                {{-- @endif --}}

                            @else
                                <a href="{{ url('login') }}"
                                   class="btn btn-sm btn-warning">
                                    Contact Us
                                </a>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

        @empty
            <div class="col-12 text-center">
                <p class="text-muted">No media available.</p>
            </div>
        @endforelse

    </div>
</div>
