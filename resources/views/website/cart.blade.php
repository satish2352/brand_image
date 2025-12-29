{{-- @extends('website.layout')

@section('title', 'My Cart')

@section('content')


<div class="container my-5">

  <div class="d-flex justify-content-end">
  
  </div>


    <h3 class="mb-4">My Cart</h3>

    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

   
    @if($items->count() === 0)
        <p>Your cart is empty.</p>
        <a href="{{ url('/') }}" class="btn btn-primary">Go Back</a>
    @else

    <table class="table table-bordered text-center align-middle">
        <thead>
            <tr>
                <th>Media</th>
                <th>Price</th>
             
                <th>Total</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
        @php $grandTotal = 0; @endphp

        @foreach($items as $item)
            @php
                $total = $item->price * $item->qty;
                $grandTotal += $total;
            @endphp

            <tr>
                <td>
                    {{ $item->media_title ?? $item->category_name }}
                </td>

                <td>
                    ₹ {{ number_format($item->price, 2) }}
                </td>

               

                <td>
                    ₹ {{ number_format($total, 2) }}
                </td>

                <td>
                    <a href="{{ route('cart.remove', base64_encode($item->id)) }}"
                       class="btn btn-sm btn-danger">
                        Remove
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

        <tfoot>
            <tr>
                <th colspan="3">Grand Total</th>
                <th colspan="2">
                    ₹ {{ number_format($grandTotal, 2) }}
                </th>
            </tr>
        </tfoot>
    </table>

    <div class="text-end mt-3">
        <a href="{{ url('/') }}" class="btn btn-outline-secondary">
            Continue Shopping
        </a>

<form action="{{ route('checkout.create') }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-success">
        Proceed to Checkout
    </button>
</form>
@if($items->count() > 0)
<button type="button"
    class="btn btn-success"
    data-bs-toggle="modal"
    data-bs-target="#campaignModal">
    Campaign
</button>
@endif

     
    </div>

    @endif
</div>



<div class="modal fade" id="campaignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="{{ route('campaign.store') }}">
  
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Create Campaign</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Campaign Name</label>
                        <input type="text"
                               name="campaign_name"
                               class="form-control"
                               placeholder="Enter campaign name"
                               required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            class="btn btn-success">
                        Save
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection --}}
@extends('website.layout')

@section('title', 'My Cart')

@section('content')

<style>
.cart-card {
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    background: #fff;
    padding: 12px;
    height: 100%;
}

.cart-img-wrapper {
    display: flex;
    gap: 10px;
}

.cart-thumbs {
    width: 70px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.cart-thumbs img {
    width: 100%;
    height: 60px;
    object-fit: cover;
    border: 1px solid #ddd;
    cursor: pointer;
    border-radius: 4px;
}

.cart-thumbs img.active {
    border: 2px solid #0d6efd;
}

.cart-main-img {
    flex: 1;
}

.cart-main-img img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #eee;
}

.cart-info h6 {
    font-weight: 600;
    margin-bottom: 4px;
}

.cart-info p {
    margin-bottom: 4px;
    font-size: 14px;
}

.cart-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    /* border-top: 1px solid #eee; */
    padding-top: 8px;
}
.cart-main-img {
    position: relative;
    overflow: hidden;
    cursor: zoom-in;
}

.cart-main-img img {
    transition: transform 0.3s ease;
    transform-origin: center center;
}

/* Zoom active */
.cart-main-img.zoom-active img {
    transform: scale(2);
}
</style>

<div class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>My Cart</h3>
        <a href="{{ url('/') }}" class="btn btn-outline-secondary">
            Continue Shopping
        </a>
    </div>

    @if($items->isEmpty())
        <div class="text-center py-5">
            <h5>Your cart is empty</h5>
        </div>
    @else

    <div class="row">

        @php $grandTotal = 0; @endphp

        @foreach($items as $item)

            @php
                $total = $item->price * $item->qty;
                $grandTotal += $total;
                $firstImage = $item->images->first();
            @endphp

            <div class="col-lg-12 mb-4">
                <div class="cart-card ">

                <div class="row">
                     <div class="col-lg-5 col-md-5 col-sm-5">
                       {{-- IMAGE SECTION --}}
                    <div class="cart-img-wrapper">

                        {{-- THUMBNAILS --}}
                        <div class="cart-thumbs">
                            @foreach($item->images as $key => $img)
                                <img
                                    src="{{ config('fileConstants.IMAGE_VIEW') . $img->images }}"
                                    class="{{ $key === 0 ? 'active' : '' }}"
                                    onclick="changeCartImage(this, '{{ config('fileConstants.IMAGE_VIEW') . $img->images }}')"
                                >
                            @endforeach
                        </div>

                        {{-- MAIN IMAGE --}}
                        {{-- <div class="cart-main-img">
                            <img
                                src="{{ $firstImage ? config('fileConstants.IMAGE_VIEW') . $firstImage->images : '' }}"
                                class="main-cart-image">
                        </div> --}}
<div class="cart-main-img"
     onmousemove="zoomImage(event, this)"
     onmouseleave="resetZoom(this)">
     
    <img
        src="{{ $firstImage ? config('fileConstants.IMAGE_VIEW') . $firstImage->images : '' }}"
        class="main-cart-image">
</div>

                    </div>
                 </div>
                  <div class="col-lg-6 col-md-6 col-sm-6">
                    {{-- DETAILS --}}
                    <div class="cart-info mt-2">
                        <h6>{{ $item->media_title ?? $item->category_name }}</h6>

                        <p class="text-muted">
                            📍 {{ $item->area_name ?? 'N/A' }}
                        </p>

                        <p>
                            📅
                            {{ \Carbon\Carbon::parse($item->from_date)->format('d M Y') }}
                            →
                            {{ \Carbon\Carbon::parse($item->to_date)->format('d M Y') }}
                        </p>

                        <p class="text-success fw-bold">
                           Monthly Price :  ₹ {{ number_format($item->price, 2) }}
                        </p>
<p class="text-success fw-bold">
    ₹ {{ number_format($item->per_day_price, 2) }} / day
</p>

<p class="fw-bold">
    Total: ₹ {{ number_format($item->total_price, 2) }}
</p>

                        
                    </div>
                </div>

                  {{-- FOOTER --}}
                  <div class="col-lg-1 col-md-1 col-sm-1">
                    <div class="cart-footer">
                        {{-- <strong>Total: ₹ {{ number_format($total, 2) }}</strong> --}}

                        <a href="{{ route('cart.remove', base64_encode($item->id)) }}"
                           class="btn btn-sm btn-danger">
                            Remove
                        </a>
                    </div>
                </div>
                </div>
                  

                </div>
            </div>

        @endforeach

    </div>

    {{-- GRAND TOTAL --}}
    {{-- <div class="d-flex justify-content-between align-items-center mt-4">
        <h5>
            Grand Total:
            <span class="text-success">
                ₹ {{ number_format($grandTotal, 2) }}
            </span>
        </h5>

        <div>
            <form action="{{ route('checkout.create') }}" method="POST" class="d-inline">
                @csrf
                <button class="btn btn-success">
                    Proceed to Checkout
                </button>
            </form>

            <button class="btn btn-primary ms-2"
                    data-bs-toggle="modal"
                    data-bs-target="#campaignModal">
                Create Campaign
            </button>
        </div>
    </div> --}}
<div class="row align-items-center mt-4">

    {{-- LEFT : GRAND TOTAL (5 columns) --}}
    <div class="col-md-5">
        
    </div>

    {{-- RIGHT : ACTION BUTTONS --}}
    <div class="col-md-7 ">
        @php $grandTotal = 0; @endphp
@foreach($items as $item)
    @php $grandTotal += $item->total_price; @endphp
@endforeach
        <h5 class="mb-0 text-start">
            Grand Total:
            <span class="text-success">
                ₹ {{ number_format($grandTotal, 2) }}
            </span>
        </h5>

       



        <div class="text-end">
        <form action="{{ route('checkout.create') }}" method="POST" class="d-inline">
            @csrf
            <button class="btn btn-success">
                Proceed to Checkout
            </button>
        </form>

        <button class="btn btn-primary ms-2"
                data-bs-toggle="modal"
                data-bs-target="#campaignModal">
            Create Campaign
        </button>
        </div>
    </div>

</div>

    @endif
</div>

{{-- IMAGE SWITCH SCRIPT --}}
<script>
function changeCartImage(el, src) {
    const wrapper = el.closest('.cart-img-wrapper');
    const mainImg = wrapper.querySelector('.main-cart-image');

    wrapper.querySelectorAll('.cart-thumbs img')
        .forEach(img => img.classList.remove('active'));

    el.classList.add('active');
    mainImg.src = src;
}
</script>

{{-- CAMPAIGN MODAL --}}
<div class="modal fade" id="campaignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('campaign.store') }}">
                @csrf
                <div class="modal-header">
                    <h5>Create Campaign</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="text"
                           name="campaign_name"
                           class="form-control"
                           placeholder="Campaign name"
                           required>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button class="btn btn-success">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function zoomImage(e, container) {
    const img = container.querySelector('img');
    const rect = container.getBoundingClientRect();

    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;

    img.style.transformOrigin = `${x}% ${y}%`;
    container.classList.add('zoom-active');
}

function resetZoom(container) {
    const img = container.querySelector('img');
    container.classList.remove('zoom-active');
    img.style.transformOrigin = 'center center';
}

/* Existing thumbnail switch (unchanged) */
function changeCartImage(el, src) {
    const wrapper = el.closest('.cart-img-wrapper');
    const mainImg = wrapper.querySelector('.main-cart-image');

    wrapper.querySelectorAll('.cart-thumbs img')
        .forEach(img => img.classList.remove('active'));

    el.classList.add('active');
    mainImg.src = src;
}
</script>

@endsection
