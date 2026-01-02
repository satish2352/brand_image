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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@section('title', 'My Cart')

@section('content')

<style>
.cart-card{
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(6px);
    border-radius: 16px;
    padding: 18px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 18px 40px rgba(0,0,0,0.08);
    transition: all 0.35s ease;
}

.cart-card:hover{
    transform: translateY(-6px);
    box-shadow: 0 25px 55px rgba(0,0,0,0.12);
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

.cart-thumbs img{
    border-radius: 8px;
    opacity: 0.75;
    transition: 0.3s;
}

.cart-thumbs img:hover,
.cart-thumbs img.active{
    opacity: 1;
    border-color: #ff9800;
}

.cart-main-img{
    border-radius: 14px;
    overflow: hidden;
    background: #f8f8f8;
}

.cart-main-img img{
    border-radius: 14px;
    transition: transform 0.4s ease;
}

.cart-main-img:hover img{
    transform: scale(1.08);
}

.cart-info h6{
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}

.cart-info p{
    font-size: 14px;
    margin-bottom: 6px;
    color: #555;
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

.remove-btn{
    border-width: 1.5px;
}

.remove-btn:hover{
    background: #dc3545;
    color: #fff;
}

.cart-btn{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    padding: 10px 20px;
    border-radius: 14px;

    font-size: 15px;
    font-weight: 600;
    text-decoration: none;

    cursor: pointer;
    border: none;
    outline: none;

    transition: all 0.3s ease;
}

.cart-btn-primary{
    background: linear-gradient(135deg, #ff9800, #ffb703);
    color: #fff;
}

.cart-btn-primary:hover{
    transform: translateY(-3px);
    box-shadow: 0 14px 32px rgba(255,152,0,0.4);
}

.cart-btn-dark{
    background: #ffc107;
    color: #fff;
}

.cart-btn-dark:hover{
    background: #ffc107;
    transform: translateY(-3px);
}

.cart-btn-outline{
    background: transparent;
    border: 1.5px solid rgba(0,0,0,0.35);
    color: #ffc107;
}

.cart-btn-outline:hover{
    border-color: #ff9800;
    color: #ff9800;
    transform: translateY(-3px);
}

@media(max-width: 575px){
    .cart-btn{
        width: 100%;
        margin-top: 10px;
    }
}

.cart-summary{
    background: linear-gradient(
        135deg,
        #fff7e6 0%,
        #ffffff 45%,
        #fff1d6 100%
    );
    border: 1px solid rgba(255,165,0,0.35);
    border-radius: 20px;
    padding: 20px 30px;
    box-shadow: 0 20px 45px rgba(0,0,0,0.08);
}

.campaign-modal-footer{
    padding: 10px 26px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;

    border-top: 1px solid #eee;
}

/* Base */
.campaign-btn{
    padding: 8px 18px;
    border-radius: 12px;

    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;

    transition: all 0.3s ease;
}

/* Outline */
.campaign-btn-outline{
    background: transparent;
    border: 1.5px solid #ccc;
    color: #333;
}

.campaign-btn-outline:hover{
    border-color: #ff9800;
    color: #ff9800;
}

/* Primary */
.campaign-btn-primary{
    background: linear-gradient(135deg, #ff9800, #ffb703);
    color: #000;
}

.campaign-btn-primary:hover{
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(255,152,0,0.4);
}

@media(max-width: 575px){
    .campaign-modal-footer{
        flex-direction: column;
    }

    .campaign-btn{
        width: 100%;
    }
}

.campaign-modal-content{
    border-radius: 20px;
    border: none;

    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(12px);

    box-shadow: 0 30px 80px rgba(0,0,0,0.25);
    overflow: hidden;
}

.campaign-modal-header{
    padding: 18px 26px;
    display: flex;
    justify-content: space-between;
    align-items: center;

    border-bottom: 1px solid #eee;
}

.campaign-modal-header h5{
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #111;
}

.campaign-close{
    background: transparent;
    border: none;
    font-size: 28px;
    line-height: 1;
    cursor: pointer;
    color: #999;
    transition: 0.3s;
}

.campaign-close:hover{
    color: #000;
}

.campaign-modal-body{
    padding: 26px;
}

.campaign-label{
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.campaign-label .required{
    color: #ff9800;
}

.campaign-input{
    width: 100%;
    padding: 10px 14px;
    border-radius: 12px;
    border: 1.5px solid #ddd;

    font-size: 15px;
    outline: none;
    transition: 0.3s;
}

.campaign-input:focus{
    border-color: #ff9800;
    box-shadow: 0 0 0 3px rgba(255,152,0,0.15);
}

/* Booked dates */
.flatpickr-day.booked-date {
    background: #dc3545 !important;
    color: #fff !important;
    border-radius: 50%;
    cursor: not-allowed;
}

/* Selected range */
.flatpickr-day.inRange,
.flatpickr-day.startRange,
.flatpickr-day.endRange {
    background: #28a745 !important;
    color: #fff !important;
}

.update-date-btn{
    padding: 4px 12px !important;
    font-size: 13px !important;
    border-radius: 8px !important;
    width: 140px;
}

</style>

	<!-- breadcrumb-section -->
	<div class="container-fluid about-banner-img g-0">
		<div class="row">
			<div class="col-md-12">
				<img src="{{ asset('assets/img/cart.png') }}"
					alt="About Banner"
					class="img-fluid">
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

<div class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>My Cart</h3>
        {{-- <a href="{{ url('/') }}" class="btn btn-outline-secondary">
            Continue Shopping
        </a> --}}
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

                        {{-- <p class="text-muted"> --}}
                        <p class="badge bg-light text-dark">
                            📍 {{ $item->area_name ?? 'N/A' }}
                        </p>

                        {{-- <p>
                            📅
                            {{ \Carbon\Carbon::parse($item->from_date)->format('d M Y') }}
                            →
                            {{ \Carbon\Carbon::parse($item->to_date)->format('d M Y') }}
                        </p> --}}
                        @if($item->from_date && $item->to_date)
                        <p class="text-muted">
                            📅
                            {{ \Carbon\Carbon::parse($item->from_date)->format('d M Y') }}
                            →
                            {{ \Carbon\Carbon::parse($item->to_date)->format('d M Y') }}
                        </p>
                        @endif

<div class="price-box mt-2">
    <div class="text-muted small">Monthly Price</div>
    <div class="fw-bold text-success fs-6">
        ₹ {{ number_format($item->price, 2) }}
    </div>

    <div class="small text-muted">
        ₹ {{ number_format($item->per_day_price, 2) }} / day
    </div>

    <div class="mt-2 fw-bold fs-5">
        Total: <span class="text-success">
        ₹ {{ number_format($item->total_price, 2) }}
        </span>
    </div>
</div>

<form class="cart-date-form mt-3">
    @csrf

    <input type="hidden" name="cart_item_id" value="{{ $item->id }}">
    <input type="hidden" name="from_date" class="from-date">
    <input type="hidden" name="to_date" class="to-date">

    {{-- <div class="cart-calendar"
         id="calendar_{{ $item->id }}"
         data-media-id="{{ $item->media_id }}">
    </div> --}}
    <div class="row">

    <div class="cart-calendar"
         id="calendar_{{ $item->id }}"
         data-media-id="{{ $item->media_id }}"
         data-from-date="{{ $item->from_date }}"
         data-to-date="{{ $item->to_date }}">
    </div>

    <!-- 🔴 ERROR MESSAGE (ABOVE BUTTON) -->
    <small class="text-danger cart-date-error d-none mt-2">
        Please select booking dates
    </small>

    <!-- 🟡 SMALL UPDATE BUTTON -->
    <div class="d-flex justify-content-start">
        <button type="button"
            class="btn btn-warning btn-sm mt-2 update-date-btn">
        Update Dates
    </button>
    </div>

</div>

    <small class="text-danger cart-date-error d-none"></small>
</form>


                        
                    </div>
                </div>

                  {{-- FOOTER --}}
                  <div class="col-lg-1 col-md-1 col-sm-1">
                    <div class="cart-footer">
                        {{-- <strong>Total: ₹ {{ number_format($total, 2) }}</strong> --}}

                    <a href="{{ route('cart.remove', base64_encode($item->id)) }}"
                    class="btn btn-outline-danger remove-btn btn-sm rounded-pill">
                    <i class="bi bi-trash"></i>
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

    <div class="cart-summary mt-5">
        <div class="row align-items-center">

            <!-- LEFT : GRAND TOTAL -->
            <div class="col-md-4">
                @php $grandTotal = 0; @endphp
                @foreach($items as $item)
                    @php $grandTotal += $item->total_price; @endphp
                @endforeach
                <h5 class="summary-label">Grand Total</h5>
                <h3 class="summary-amount">
                    ₹ {{ number_format($grandTotal, 2) }}
                </h3>
            </div>

            <!-- RIGHT : ACTION BUTTONS -->
            <div class="col-md-8 text-md-end mt-3 mt-md-0">

                <a href="{{ url('/') }}" class="btn cart-btn btn-cart-outline">
                    Continue Shopping
                </a>

                {{-- <button class="btn cart-btn cart-btn-dark ms-2"
                        data-bs-toggle="modal"
                        data-bs-target="#campaignModal">
                    Create Campaign
                </button> --}}
                <button class="btn cart-btn cart-btn-dark ms-2"
        type="button"
        onclick="openCampaignModal()">
    Create Campaign
</button>

<form action="{{ route('checkout.create') }}"
      method="POST"
      class="d-inline"
      onsubmit="return validateCartDates();">
    @csrf
    <button class="btn cart-btn cart-btn-primary ms-2">
        Proceed to Checkout
    </button>
</form>

                {{-- <form action="{{ route('checkout.create') }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn cart-btn cart-btn-primary ms-2">
                        Proceed to Checkout
                    </button>
                </form> --}}

            </div>
        </div>
    </div>
    {{-- <div class="row align-items-center mt-4">

        <div class="col-md-5">
            
        </div>

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

    </div> --}}

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
        <div class="modal-content campaign-modal-content">
            <form method="POST" action="{{ route('campaign.store') }}">
                @csrf
                <div class="campaign-modal-header">
                    <h5>Create Campaign</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="campaign-modal-body">
                    <input type="text"
                           name="campaign_name"
                           class="campaign-input"
                           placeholder="Campaign name"
                           required>
                </div>

                <div class="campaign-modal-footer">
                    <button type="button" class="campaign-btn campaign-btn-outline" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="campaign-btn campaign-btn-primary">
                        Save Campaign
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
{{-- ============== --}}
<script>
document.querySelectorAll('.update-date-btn').forEach(btn => {

    btn.addEventListener('click', function () {

        const form     = this.closest('.cart-date-form');
        const fromDate = form.querySelector('.from-date').value;
        const toDate   = form.querySelector('.to-date').value;
        const errorBox = form.querySelector('.cart-date-error');

        // 🚫 stop if no dates
        if (!fromDate || !toDate) {
            errorBox.classList.remove('d-none');
            errorBox.innerText = 'Please select booking dates';
            return;
        }

        const formData = new FormData(form);

        fetch("{{ route('cart.update.dates') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                errorBox.classList.remove('d-none');
                errorBox.innerText = data.message;
            } else {
                // ✅ CLEAR ERROR BEFORE RELOAD
                errorBox.classList.add('d-none');
                errorBox.innerText = '';
                location.reload();
            }
        })
        .catch(() => {
            errorBox.classList.remove('d-none');
            errorBox.innerText = 'Something went wrong';
        });
    });

});
</script>



<script>
document.querySelectorAll('.cart-calendar').forEach(calendar => {

    const mediaId = calendar.dataset.mediaId;
    const fromDate = calendar.dataset.fromDate;
    const toDate   = calendar.dataset.toDate;

    const form    = calendar.closest('.cart-date-form');
    const fromInp = form.querySelector('.from-date');
    const toInp   = form.querySelector('.to-date');
    const error   = form.querySelector('.cart-date-error');

    fetch("{{ url('/cart/booked-dates') }}/" + mediaId)
        .then(res => res.json())
        .then(bookings => {

            flatpickr(calendar, {
                mode: "range",
                inline: true,
                static: true,
                minDate: "today",
                dateFormat: "Y-m-d",

                // ✅ SAFE preload (NO Blade vars here)
                defaultDate: [fromDate, toDate],

                disable: bookings.map(b => ({
                    from: b.from_date,
                    to: b.to_date
                })),

                onReady: function (selectedDates) {
                    if (selectedDates.length === 2) {
                        fromInp.value = selectedDates[0].toISOString().split('T')[0];
                        toInp.value   = selectedDates[1].toISOString().split('T')[0];
                    }
                },

                onDayCreate: function (dObj, dStr, fp, dayElem) {
                    const date = dayElem.dateObj.toISOString().split('T')[0];
                    bookings.forEach(b => {
                        if (date >= b.from_date && date <= b.to_date) {
                            dayElem.classList.add('booked-date');
                        }
                    });
                },

                onChange: function (dates) {
                    if (dates.length === 2) {
                        fromInp.value = dates[0].toISOString().split('T')[0];
                        toInp.value   = dates[1].toISOString().split('T')[0];
                        error.classList.add('d-none');
                    }
                }
            });

        });
});

</script>


<script>
function validateCartDates() {
    let isValid = true;
    let firstInvalidForm = null;

    document.querySelectorAll('.cart-date-form').forEach(form => {

        const fromDate = form.querySelector('.from-date').value;
        const toDate   = form.querySelector('.to-date').value;
        const errorBox = form.querySelector('.cart-date-error');

        if (!fromDate || !toDate) {
            isValid = false;

            errorBox.classList.remove('d-none');
            errorBox.innerText = 'Please select booking dates';

            if (!firstInvalidForm) {
                firstInvalidForm = form;
            }
        } else {
            errorBox.classList.add('d-none');
            errorBox.innerText = '';
        }
    });

    if (!isValid && firstInvalidForm) {
        firstInvalidForm.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    return isValid;
}
</script>
<script>
function openCampaignModal() {
    if (!validateCartDates()) {
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('campaignModal'));
    modal.show();
}
</script>

@endsection
