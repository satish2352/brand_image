@php
    // $cart = session()->get('cart', []);
@endphp

<div class="container my-5">
    <div class="row">

        @forelse($mediaList as $media)

            @php
                // $inCart = isset($cart[$media->id]);
                // $qty = $inCart ? $cart[$media->id]['qty'] : 1;
            @endphp

            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="media-card">

                 

                           <img src="{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}"
                             class="img-fluid rounded"
                             style="height:150px; width:100%; object-fit:cover;">

                    <div class="media-body">

                        <h6> {{ $media->area_name }}, {{ $media->city_name }}</h6>

                        <small class="text-muted d-block">
                          {{ $media->media_title ?? $media->category_name }} 
                        </small>

                        <p class="mt-2">
                            ₹ {{ number_format($media->price, 2) }}
                        </p>

                        <div class="media-actions mt-2 d-flex justify-content-end">

                            {{-- <a href="{{ url('media/view-details/' . encrypt($media->id)) }}"
                               class="btn btn-sm btn-primary">
                                View
                            </a> --}}

                            {{-- ONLY FOR ALLOWED CATEGORIES --}}
                           {{-- ONLY FOR ALLOWED CATEGORIES --}}
                            @if(
                                $media->category_name === 'Hoardings/Billboards'
                            )

                                @auth('website')
                                    {{-- User Logged In --}}
                                    <a href="{{ route('cart.add', base64_encode($media->id)) }}"
                                    class="btn btn-sm btn-success">
                                        Add to Cart
                                    </a>
                                @else
                                    {{-- User NOT Logged In --}}
                                    {{-- <a href="{{ route('login') }}"
                                    class="btn btn-sm btn-success">
                                        Add to Cart
                                    </a> --}}

                                   <button class="btn btn-sm btn-success"
        data-bs-toggle="modal"
        data-bs-target="#authModal"
        onclick="setRedirect('{{ route('cart.add', base64_encode($media->id)) }}')">
    Add to Cart
</button>

                                @endauth

                            @else
                                <a href="{{ route('contact.create') }}"
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
