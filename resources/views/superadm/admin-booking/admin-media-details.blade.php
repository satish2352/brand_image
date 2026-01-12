
@extends('superadm.layout.master')

@section('content')
{{-- ================= FLATPICKR ================= --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
    /* parent must be relative */

.card {
    border-radius: 12px;
}

.calendar-wrapper {
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 10px;
    background: #fafafa;
}

.price {
    font-size: 22px;
    font-weight: 700;
    color: #28a745;
}

.media-info p {
    margin-bottom: 6px;
}

    /* ADD TO CART BUTTON */
.add-to-cart-btn {
    width: 100%;
    padding: 12px 18px;
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #f28123, #ff9f43);
    border: none;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(242, 129, 35, 0.35);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

/* Hover */
.add-to-cart-btn:hover {
    background: linear-gradient(135deg, #e06b0c, #ff851b);
    box-shadow: 0 6px 18px rgba(242, 129, 35, 0.45);
    transform: translateY(-1px);
}

/* Active click */
.add-to-cart-btn:active {
    transform: scale(0.98);
}

/* Disabled state (optional future use) */
.add-to-cart-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    box-shadow: none;
}
/* 🔴 Booked dates */
.flatpickr-day.booked-date {
    background: #dc3545 !important;
    color: #fff !important;
    border-radius: 50%;
    cursor: not-allowed;
}

/* 🟢 Available selected range */
.flatpickr-day.inRange,
.flatpickr-day.startRange,
.flatpickr-day.endRange {
    background: #28a745 !important;
    color: #fff !important;
}


/* IMAGE GALLERY */
.media-gallery {
    display: flex;
    gap: 12px;
}

.media-thumbs {
    width: 90px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.media-thumbs img {
    width: 100%;
    height: 70px;
    object-fit: cover;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}

.media-thumbs img.active {
    border: 2px solid #f28123;
}

/* ===== IMAGE GALLERY ===== */

.media-main {
    position: relative;
    overflow: hidden;
    border-radius: 10px;
    cursor: zoom-in;
}

.media-main img {
    width: 100%;
    height: 420px;
    object-fit: cover;
    transition: transform 0.35s ease;
}

.media-main.zoom-active img {
    transform: scale(2);
}

/* Thumbnails row */
.media-thumbs-bottom {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 6px;
}

.media-thumbs-bottom::-webkit-scrollbar {
    height: 5px;
}
.media-thumbs-bottom::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 10px;
}

.thumb-img {
    height: 80px;
    min-width: 110px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.25s ease;
}

.thumb-img:hover {
    transform: scale(1.03);
}

.thumb-img.active {
    border-color: #f28123;
}

/* Mobile */
@media (max-width: 768px) {
    .media-main img {
        height: 260px;
    }
    .thumb-img {
        height: 65px;
        min-width: 90px;
    }
}


/* INFO */
.media-info h4 {
    font-weight: 600;
}

.price {
    font-size: 18px;
    font-weight: 700;
    color: #28a745;
}

.per-day {
    font-size: 14px;
    color: #666;
}

.add-to-cart-btn:disabled {
    background: #ddd;
    color: #888;
}

.add-to-cart-btn:not(:disabled) {
    background: linear-gradient(135deg, #f28123, #ff9f43);
    cursor: pointer;
}

</style>
@php
    $width  = (float) $media->width;
    $height = (float) $media->height;
    $sqft   = $width * $height;
@endphp




{{-- ================= MEDIA DETAILS ================= --}}
<div class="mt-150 mb-150">
<div class="container">

    <div class="card shadow-sm border-0 p-4">

        <div class="row g-4">

            {{-- ================= LEFT : IMAGE GALLERY ================= --}}
            <div class="col-lg-5">

                {{-- <div class="media-main mb-3"
                     onmousemove="zoomMedia(event,this)"
                     onmouseleave="resetZoom(this)">
                    <img class="main-media-image"
                         id="mainMediaImage"
                         src="{{ config('fileConstants.IMAGE_VIEW') . $media->images[0]->images }}">
                </div>

                <div class="media-thumbs-bottom">
                    @foreach($media->images as $k => $img)
                        <img src="{{ config('fileConstants.IMAGE_VIEW') . $img->images }}"
                             class="thumb-img {{ $k==0 ? 'active' : '' }}"
                             onclick="changeMediaImage(this,'{{ config('fileConstants.IMAGE_VIEW') . $img->images }}')">
                    @endforeach
                </div> --}}
                 <h3 class="fw-bold mb-1">{{ $media->media_title }}</h3>

                <p class="text-muted mb-2">
                    <i class="fas fa-map-marker-alt text-danger"></i>
                    {{ $media->area_name }}, {{ $media->city_name }}
                </p>

                <hr>

                <div class="row small">
                    <div class="col-6 mb-2"><strong>Category:</strong> {{ $media->category_name }}</div>
                    <div class="col-6 mb-2"><strong>Media Code:</strong> {{ $media->media_code }}</div>
                    <div class="col-6 mb-2"><strong>Facing:</strong> {{ $media->facing_name }}</div>
                    <div class="col-6 mb-2"><strong>Area Type:</strong> {{ $media->area_type }}</div>
                    <div class="col-6 mb-2"><strong>Illumination:</strong> {{ $media->illumination_name }}</div>

                    <div class="col-6 mb-2">
                        <strong>Size:</strong>
                        {{ number_format($width,2) }} × {{ number_format($height,2) }} ft
                    </div>

                    <div class="col-6 mb-2">
                        <strong>Total Area:</strong>
                        {{ number_format($sqft,2) }} SQFT
                    </div>

                    <div class="col-12 mb-2">
                        <strong>Address:</strong> {{ $media->address }}
                    </div>
                </div>
                 <hr>

                {{-- PRICE --}}
                <div class="mb-3">
                    <span class="price">₹ {{ number_format($media->price,2) }}</span>
                    <span class="text-muted"> / month</span>
                    <div class="per-day">₹ {{ number_format($media->per_day_price,2) }} / day</div>
                </div>

            </div>

            {{-- ================= RIGHT : DETAILS + CALENDAR ================= --}}
            <div class="col-lg-7">

               

               

                {{-- CALENDAR --}}
                

                <form method="POST" action="{{ route('admin.booking.store') }}">
                    @csrf
                    <input type="hidden" name="media_id" value="{{ base64_encode($media->id) }}">
                    <input type="hidden" name="from_date" id="from_date">
                    <input type="hidden" name="to_date" id="to_date">

                    <div class="mb-3">
    <label>Full Name <span class="text-danger">*</span></label>
    <input type="text"
           name="signup_name"
           class="form-control @error('signup_name') is-invalid @enderror"
           value="{{ old('signup_name') }}">

    @error('signup_name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
                               <div class="row">
                                <div class="col-md-6 mb-3">
    <label>Email Id <span class="text-danger">*</span></label>
    <input type="email"
           name="signup_email"
           class="form-control @error('signup_email') is-invalid @enderror"
           value="{{ old('signup_email') }}">

    @error('signup_email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>


                              <div class="col-md-6 mb-3">
    <label>Mobile Number <span class="text-danger">*</span></label>
    <input type="text"
           name="signup_mobile_number"
           class="form-control @error('signup_mobile_number') is-invalid @enderror"
           value="{{ old('signup_mobile_number') }}">

    @error('signup_mobile_number')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

                               </div>

<h6 class="fw-bold mt-3">Select Booking Dates</h6>
                    <div class="calendar-wrapper mt-2">
                        <input type="text" id="booking_range" class="d-none">
                    </div>

                    <div class="text-danger small mt-2 d-none" id="dateError">
                        Please select booking dates
                    </div>

                    

                    <div class="mt-3">
    <p>Total Days: <strong id="totalDays">0</strong></p>
    <p>Amount: ₹ <strong id="baseAmount">0</strong></p>
    <p>GST (18%): ₹ <strong id="gstAmount">0</strong></p>
    <hr>
    <p class="fw-bold">
        Grand Total: ₹ <span id="grandTotal">0</span>
    </p>
</div>

<input type="hidden" name="total_amount" id="total_amount">
<input type="hidden" name="gst_amount" id="gst_amount">
<input type="hidden" name="grand_total" id="grand_total">


                    <button type="submit"
                            class="add-to-cart-btn mt-4"
                            id="addToCartBtn"
                            disabled>
                        <i class="fas fa-shopping-cart"></i> Book
                    </button>
                </form>

            </div>

        </div>
    </div>

</div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function (e) {

    const name   = document.querySelector('[name="signup_name"]').value.trim();
    const email  = document.querySelector('[name="signup_email"]').value.trim();
    const mobile = document.querySelector('[name="signup_mobile_number"]').value.trim();

    if (!name || !email || !mobile) {
        alert('Please fill Name, Email and Mobile Number');
        e.preventDefault();
        return;
    }

    if (!/^\d{10,12}$/.test(mobile)) {
        alert('Mobile number must be 10 to 12 digits');
        e.preventDefault();
    }
});
</script>


<script>
function changeMediaImage(el, src) {
    document.getElementById('mainMediaImage').src = src;

    document.querySelectorAll('.thumb-img').forEach(img => {
        img.classList.remove('active');
    });

    el.classList.add('active');
}

/* Zoom logic */
function zoomMedia(e, container) {
    container.classList.add('zoom-active');

    const img = container.querySelector('img');
    const rect = container.getBoundingClientRect();

    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;

    img.style.transformOrigin = `${x}% ${y}%`;
}

function resetZoom(container) {
    container.classList.remove('zoom-active');
    const img = container.querySelector('img');
    img.style.transformOrigin = 'center center';
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const bookedRanges = @json($bookedRanges ?? []);
    const pricePerDay = {{ (float) $media->per_day_price }};
    const MIN_DAYS = 15;

    const fromInput = document.getElementById('from_date');
    const toInput   = document.getElementById('to_date');
    const addBtn    = document.getElementById('addToCartBtn');
    const errorBox  = document.getElementById('dateError');

    flatpickr("#booking_range", {
        mode: "range",
        minDate: "today",
        dateFormat: "Y-m-d",
        inline: true,
        static: true,

        //  Disable booked dates
        disable: bookedRanges.map(r => ({
            from: r.from_date,
            to: r.to_date
        })),

        //  Mark booked dates RED
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            const date = dayElem.dateObj.toISOString().split('T')[0];

            bookedRanges.forEach(range => {
                if (date >= range.from_date && date <= range.to_date) {
                    dayElem.classList.add('booked-date');
                }
            });
        },

        onChange: function (selectedDates) {

            addBtn.disabled = true;
            errorBox.classList.add('d-none');

            if (selectedDates.length !== 2) return;

            const start = selectedDates[0];
            const end   = selectedDates[1];

            const diffDays =
                Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;

            if (diffDays < MIN_DAYS) {
                errorBox.innerText = `Minimum booking period is ${MIN_DAYS} days`;
                errorBox.classList.remove('d-none');
                return;
            }

            const baseAmount = diffDays * pricePerDay;
            const gstAmount  = +(baseAmount * 0.18).toFixed(2);
            const grandTotal = +(baseAmount + gstAmount).toFixed(2);

            document.getElementById('totalDays').innerText   = diffDays;
            document.getElementById('baseAmount').innerText = baseAmount.toFixed(2);
            document.getElementById('gstAmount').innerText  = gstAmount.toFixed(2);
            document.getElementById('grandTotal').innerText = grandTotal.toFixed(2);

            document.getElementById('total_amount').value = baseAmount.toFixed(2);
            document.getElementById('gst_amount').value   = gstAmount.toFixed(2);
            document.getElementById('grand_total').value  = grandTotal.toFixed(2);

            fromInput.value = flatpickr.formatDate(start, "Y-m-d");
            toInput.value   = flatpickr.formatDate(end, "Y-m-d");

            addBtn.disabled = false;
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    @if(session('success'))
<script>
Swal.fire({
    icon: 'success',
    title: 'Booking Confirmed 🎉',
    text: "{{ session('success') }}",
    confirmButtonText: 'OK',
    confirmButtonColor: '#f28123'
});
</script>
@endif

@if(session('error'))
<script>
Swal.fire({
    icon: 'error',
    title: 'Booking Failed ❌',
    text: "{{ session('error') }}",
    confirmButtonText: 'Try Again',
    confirmButtonColor: '#dc3545'
});
</script>
@endif

</script>
@endsection