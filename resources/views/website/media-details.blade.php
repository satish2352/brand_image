@extends('website.layout')

@section('title', $media->media_title)

@section('content')

    {{-- ================= FLATPICKR ================= --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        /* Disable hover color change for disabled dates */
        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.flatpickr-disabled:hover {
            color: #979393 !important;
            /* keep same disabled color */
            background: transparent !important;
            cursor: not-allowed;
        }

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
            border-radius: 5%;
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
        $width = (float) $media->width;
        $height = (float) $media->height;
        $sqft = $width * $height;
    @endphp


    {{-- ================= BREADCRUMB ================= --}}
    <!-- <div class="container-fluid about-banner-img g-0">
                                                                                                <div class="row">
                                                                                                    <div class="col-md-12">
                                                                                                        <img src="{{ asset('assets/img/viewdetail.png') }}" alt="About Banner" class="img-fluid">
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div> -->

    <div class="container-fluid about-banner-img g-0">
        <div class="row">
            <!-- Desktop Image -->
            <div class="col-md-12 d-none d-md-block">
                <img src="{{ asset('assets/img/viewdetail2.png') }}" alt="About Banner" class="img-fluid">
            </div>

            <!-- Mobile Image -->
            <div class="col-md-12 d-block d-md-none">
                <img src="{{ asset('assets/img/mobilviewdetail(1).png') }}" alt="About Banner" class="img-fluid">
            </div>
        </div>
    </div>
    <div class="container-fluid about-banner-img g-0">
        <!-- <div class="row">
                                                                                                        Desktop Image
                                                                                                        <div class="col-md-12 d-none d-md-block">
                                                                                                            <img src="{{ asset('assets/img/viewdetail.png') }}" alt="About Banner" class="img-fluid">
                                                                                                        </div>

                                                                                                        Mobile Image
                                                                                                        <div class="col-md-12 d-block d-md-none">
                                                                                                            <img src="{{ asset('assets/img/mobile_add_to_cart.png') }}" alt="About Banner" class="img-fluid">
                                                                                                        </div>
                                                                                                    </div> -->
        {{-- ================= MEDIA DETAILS ================= --}}
        <div id="media-details" class="mt-150 mb-150">
            <div class="container">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="card shadow-sm border-0 p-3">

                            {{-- MAIN IMAGE --}}
                            {{-- <div class="media-main mb-3" onmousemove="zoomMedia(event,this)" onmouseleave="resetZoom(this)">
                            <img class="main-media-image" id="mainMediaImage"
                                src="{{ config('fileConstants.IMAGE_VIEW') . $media->images[0]->images }}">
                    </div> --}}
                            @php
                                $mainImage = $media->images->first();
                            @endphp
                            <div class="media-main mb-3" onmousemove="zoomMedia(event,this)" onmouseleave="resetZoom(this)">
                                <img class="main-media-image" id="mainMediaImage"
                                    src="{{ $mainImage ? config('fileConstants.IMAGE_VIEW') . $mainImage->images : asset('assets/img/no-image.jpg') }}">
                            </div>

                            {{-- THUMBNAILS --}}
                            {{-- <div class="media-thumbs-bottom">
                            @foreach ($media->images as $k => $img)
                                <img src="{{ config('fileConstants.IMAGE_VIEW') . $img->images }}"
                    class="thumb-img {{ $k == 0 ? 'active' : '' }}"
                    onclick="changeMediaImage(this,'{{ config('fileConstants.IMAGE_VIEW') . $img->images }}')">
                    @endforeach
                </div> --}}
                            @if ($media->images->count())
                                <div class="media-thumbs-bottom">
                                    @foreach ($media->images as $k => $img)
                                        <img src="{{ config('fileConstants.IMAGE_VIEW') . $img->images }}"
                                            class="thumb-img {{ $k == 0 ? 'active' : '' }}"
                                            onclick="changeMediaImage(this,'{{ config('fileConstants.IMAGE_VIEW') . $img->images }}')">
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mt-2">No images available</p>
                            @endif


                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card shadow-sm border-0 p-4">

                            <h3 class="fw-bold mb-2 detail-title">{{ $media->area_name }} {{ $media->facing }}</h3>

                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt text-danger"></i>
                                {{ $media->address }}, {{ $media->city_name }}
                            </p>

                            <hr>

                            <div class="row ">
                                <div class="col-6 mb-2"><strong>Media Type:</strong> {{ $media->category_name }}</div>
                                <div class="col-6 mb-2"><strong>Media Tilte:</strong> {{ $media->media_title }}</div>

                                <div class="col-6 mb-2"><strong>Facing:</strong> {{ $media->facing }}</div>
                                <div class="col-6 mb-2"><strong>Area Type:</strong> {{ $media->area_type }}</div>

                                {{-- <div class="col-6 mb-2"><strong>Radius:</strong> {{ $media->radius }} KM
                </div> --}}
                                <div class="col-6 mb-2"><strong>Illumination:</strong> {{ $media->illumination_name }}
                                </div>
                                <div class="col-6 mb-2">
                                    <strong>Size:</strong>
                                    {{ number_format($width, 2) }} x {{ number_format($height, 2) }} ft
                                </div>

                                <div class="col-6 mb-2">
                                    <strong>Total Area:</strong>
                                    {{ number_format($sqft, 2) }} SQFT
                                </div>

                                <div class="col-6 mb-2">
                                    <span class="price">₹ {{ number_format($media->price, 2) }}</span>
                                    <span class="text-muted">/ month</span>
                                    <div class="per-day">₹ {{ number_format($media->per_day_price, 2) }} / day</div>
                                </div>
                                {{-- <div class="col-6 mb-2"><strong>Size:</strong> {{ $media->width }} x {{ $media->height }}
            </div> --}}

                            </div>

                            <hr>



                            {{-- CALENDAR --}}
                            <h6 class="fw-bold mt-1">Select Booking Dates</h6>

                            <form method="POST" action="{{ route('cart.add.with.date') }}" id="addToCartForm">

                                @csrf
                                <input type="hidden" name="media_id" value="{{ base64_encode($media->id) }}">
                                <input type="hidden" name="from_date" id="from_date">
                                <input type="hidden" name="to_date" id="to_date">

                                <div class="calendar-wrapper mt-2">
                                    <input type="text" id="booking_range" class="d-none">
                                </div>

                                <div class="text-danger small mt-2 d-none" id="dateError">
                                    Please select booking dates
                                </div>

                                @auth('website')
                                    <button type="submit" class="add-to-cart-btn mt-4" id="addToCartBtn" disabled>
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                @else
                                    <button type="button" class="add-to-cart-btn mt-4" data-bs-toggle="modal"
                                        data-bs-target="#authModal">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                @endauth
                            </form>

                        </div>

                    </div>

                </div>
            </div>
        </div>
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
            document.addEventListener('DOMContentLoaded', function() {

                let bookedRanges = @json($bookedRanges ?? []);

                const form = document.getElementById('addToCartForm');

                const fromInput = document.getElementById('from_date');
                const toInput = document.getElementById('to_date');
                const errorBox = document.getElementById('dateError');
                const addBtn = document.getElementById('addToCartBtn');

                const MIN_DAYS = 15;

                // Convert merged date ranges to single-day array
                function expandRanges(ranges) {
                    let days = [];
                    ranges.forEach(r => {
                        let current = new Date(r.from_date);
                        let end = new Date(r.to_date);
                        while (current <= end) {
                            days.push(flatpickr.formatDate(current, "Y-m-d"));
                            current.setDate(current.getDate() + 1);
                        }
                    });
                    return days;
                }

                let disabledDates = expandRanges(bookedRanges);

                flatpickr("#booking_range", {
                    mode: "range",
                    minDate: "today",
                    dateFormat: "Y-m-d",
                    inline: true,
                    static: true,

                    disable: disabledDates,

                    onDayCreate: function(_, __, ___, dayElem) {
                        const date = flatpickr.formatDate(dayElem.dateObj, "Y-m-d");
                        if (disabledDates.includes(date)) {
                            dayElem.classList.add('booked-date');
                        }
                    },

                    onChange: function(selectedDates) {
                        addBtn.disabled = true;
                        errorBox.classList.add('d-none');

                        if (selectedDates.length !== 2) return;

                        const start = selectedDates[0];
                        const end = selectedDates[1];

                        const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;

                        if (diffDays < MIN_DAYS) {
                            errorBox.innerText = `Minimum booking period is ${MIN_DAYS} days`;
                            errorBox.classList.remove('d-none');
                            return;
                        }

                        fromInput.value = flatpickr.formatDate(start, "Y-m-d");
                        toInput.value = flatpickr.formatDate(end, "Y-m-d");
                        addBtn.disabled = false;
                    }
                });

                form.addEventListener('submit', function(e) {
                    if (!fromInput.value || !toInput.value) {
                        e.preventDefault();
                        errorBox.classList.remove('d-none');
                    }
                });

            });
        </script>


    @endsection
