@extends('website.layout')

@section('title', $media->media_title)

@section('content')

{{-- ================= FLATPICKR ================= --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>

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

.media-main {
    flex: 1;
    position: relative;
    overflow: hidden;
    cursor: zoom-in;
}

.media-main img {
    width: 100%;
    height: 420px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.media-main.zoom-active img {
    transform: scale(2);
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

{{-- ================= BREADCRUMB ================= --}}
<div class="breadcrumb-section breadcrumb-bg">
    <div class="container text-center">
        <p>Read the Details</p>
        <h1>{{ $media->media_title }}</h1>
    </div>
</div>

{{-- ================= MEDIA DETAILS ================= --}}
<div class="mt-150 mb-150">
<div class="container">
<div class="row">

{{-- LEFT : IMAGES --}}
<div class="col-lg-7">
    <div class="media-gallery">

        {{-- THUMBNAILS --}}
        <div class="media-thumbs">
            @foreach($media->images as $k => $img)
                <img src="{{ config('fileConstants.IMAGE_VIEW') . $img->images }}"
                     class="{{ $k==0?'active':'' }}"
                     onclick="changeMediaImage(this,'{{ config('fileConstants.IMAGE_VIEW') . $img->images }}')">
            @endforeach
        </div>

        {{-- MAIN IMAGE --}}
        <div class="media-main"
             onmousemove="zoomMedia(event,this)"
             onmouseleave="resetZoom(this)">
            <img class="main-media-image"
                 src="{{ config('fileConstants.IMAGE_VIEW') . $media->images[0]->images }}">
        </div>

    </div>
</div>

{{-- RIGHT : DETAILS --}}
<div class="col-lg-5">
<div class="media-info">

<h4>{{ $media->media_title }}</h4>

<p>
    <i class="fas fa-map-marker-alt"></i>
    {{ $media->area_name }},
    {{ $media->city_name }},
    {{ $media->district_name }},
    {{ $media->state_name }}
</p>

<p><strong>Category:</strong> {{ $media->category_name }}</p>

<p class="price">₹ {{ number_format($media->price,2) }} / month</p>
<p class="per-day">₹ {{ number_format($media->per_day_price,2) }} / day</p>

<hr>

{{-- ================= BOOKING CALENDAR ================= --}}
<h6 class="fw-bold mt-3">Select Booking Dates</h6>

<form method="POST" action="{{ route('cart.add.with.date') }}">
@csrf

<input type="hidden" name="media_id" value="{{ base64_encode($media->id) }}">
<input type="hidden" name="from_date" id="from_date">
<input type="hidden" name="to_date" id="to_date">

<div class="p-2 mt-2">
    <input type="text" id="booking_range" class="d-none">
</div>

<div class="text-danger mt-2 d-none" id="dateError">
    Please select booking dates
</div>

<button type="submit" class="add-to-cart-btn mt-4" id="addToCartBtn" disabled>
    <i class="fas fa-shopping-cart me-2"></i>
    Add to Cart
</button>

</form>


</div>
</div>

</div>
</div>
</div>

{{-- ================= SCRIPTS ================= --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    let bookedRanges = @json($bookedRanges ?? []);

    const form      = document.querySelector('form[action="{{ route('cart.add.with.date') }}"]');
    const fromInput = document.getElementById('from_date');
    const toInput   = document.getElementById('to_date');
    const errorBox  = document.getElementById('dateError');
    const addBtn    = document.getElementById('addToCartBtn'); // ✅ FIX

    flatpickr("#booking_range", {
        mode: "range",
        minDate: "today",
        dateFormat: "Y-m-d",
        inline: true,
        static: true,

        disable: bookedRanges.map(r => ({
            from: r.from_date,
            to: r.to_date
        })),

        onDayCreate: function (dObj, dStr, fp, dayElem) {
            let date = dayElem.dateObj.toISOString().split('T')[0];

            bookedRanges.forEach(function (range) {
                if (date >= range.from_date && date <= range.to_date) {
                    dayElem.classList.add('booked-date');
                }
            });
        },

        onChange: function (selectedDates) {
            if (selectedDates.length === 2) {

                fromInput.value = selectedDates[0].toISOString().split('T')[0];
                toInput.value   = selectedDates[1].toISOString().split('T')[0];

                errorBox.classList.add('d-none');
                addBtn.disabled = false; // ✅ NOW WORKS
            }
        }
    });

    // Form validation
    form.addEventListener('submit', function (e) {
        if (!fromInput.value || !toInput.value) {
            e.preventDefault();
            errorBox.classList.remove('d-none');
        }
    });

});
</script>


{{-- <script>

document.addEventListener('DOMContentLoaded', function () {

    let bookedRanges = @json($bookedRanges ?? []);

    flatpickr("#booking_range", {
        mode: "range",
        minDate: "today",
        dateFormat: "Y-m-d",
        inline: true,
        static: true,

        disable: bookedRanges.map(r => ({
            from: r.from_date,
            to: r.to_date
        })),

        onDayCreate: function (dObj, dStr, fp, dayElem) {

            let date = dayElem.dateObj.toISOString().split('T')[0];

            bookedRanges.forEach(function (range) {
                if (date >= range.from_date && date <= range.to_date) {
                    dayElem.classList.add('booked-date');
                }
            });
        },

        onChange: function (selectedDates) {
            if (selectedDates.length === 2) {
                document.getElementById('from_date').value =
                    selectedDates[0].toISOString().split('T')[0];

                document.getElementById('to_date').value =
                    selectedDates[1].toISOString().split('T')[0];
            }
        }
    });

});







/* IMAGE SWITCH */
function changeMediaImage(el, src) {
    document.querySelectorAll('.media-thumbs img')
        .forEach(i => i.classList.remove('active'));
    el.classList.add('active');
    document.querySelector('.main-media-image').src = src;
}

/* IMAGE ZOOM */
function zoomMedia(e, c) {
    const img = c.querySelector('img'),
          r = c.getBoundingClientRect();
    img.style.transformOrigin =
        ((e.clientX - r.left) / r.width) * 100 + '% ' +
        ((e.clientY - r.top) / r.height) * 100 + '%';
    c.classList.add('zoom-active');
}

function resetZoom(c) {
    c.classList.remove('zoom-active');
}
</script> --}}

@endsection
