@extends('website.layout')

@section('title', $media->media_title ?: $media->category_name)

@section('content')

    {{-- ================= FLATPICKR ================= --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        /* Disable hover color change for disabled dates */
        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.flatpickr-disabled:hover {
            color: #0F172A !important;
            /* keep same disabled color */
            background: transparent !important;
            cursor: not-allowed;
        }

        .card {
            border-radius: 12px;
        }

        .calendar-wrapper {
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 10px;
            background: #FFFFFF;
        }

        .price {
            font-size: 22px;
            font-weight: 700;
            color: #F97316;
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
            color: #FFFFFF;
            background: linear-gradient(135deg, #F97316, #F97316);
            border: none;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.35);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Hover */
        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #F97316, #F97316);
            box-shadow: 0 6px 18px rgba(249, 115, 22, 0.45);
            transform: translateY(-1px);
        }

        /* Active click */
        .add-to-cart-btn:active {
            transform: scale(0.98);
        }

        /* Disabled state (optional future use) */
        .add-to-cart-btn:disabled {
            background: #E5E7EB;
            cursor: not-allowed;
            box-shadow: none;
        }

        .flatpickr-day.booked-date {
            background: #F97316 !important;
            color: #FFFFFF !important;
            border-radius: 5%;
            overflow: visible !important;

        }

        .flatpickr-day.booked-date:hover::after {
            content: "Booked";
            position: absolute;
            left: 50%;
            height: 15px;
            line-height: 15px;
            /* aligns text */
            background: #0F172A;
            color: #FFFFFF;
            padding: 0 6px;
            font-size: 8px;
            border-radius: 3px;
            white-space: nowrap;
            margin-top: 2px;
            z-index: 9999;
            transform: translateX(-50%);
            bottom: 70%;

        }

        .flatpickr-day:not(.booked-date):not(.flatpickr-disabled):hover::after {
            content: "Available";
            position: absolute;
            left: 50%;
            height: 15px;
            line-height: 15px;
            background: #0F172A;
            color: #FFFFFF;
            padding: 0 6px;
            font-size: 8px;
            border-radius: 3px;
            white-space: nowrap;
            margin-top: 2px;
            z-index: 9999;
            transform: translatex(-50%);
            bottom: 70%;
        }



        /* 🟢 Available selected range */
        .flatpickr-day.inRange,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange {
            background: #F97316 !important;
            color: #FFFFFF !important;
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
            border: 1px solid #E5E7EB;
            border-radius: 4px;
            cursor: pointer;
        }

        .media-thumbs img.active {
            border: 2px solid #F97316;
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
            /* height: 420px; */
            /* object-fit: cover; */
            object-fit: contain !important;
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
            background: #E5E7EB;
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
            border-color: #F97316;
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
            color: #F97316;
        }

        .per-day {
            font-size: 14px;
            color: #0F172A;
        }

        .add-to-cart-btn:disabled {
            background: #E5E7EB;
            color: #0F172A;
        }

        .add-to-cart-btn:not(:disabled) {
            background: linear-gradient(135deg, #F97316, #F97316);
            cursor: pointer;
        }

        .details-contact-us:hover {
            color: #FFFFFF;
        }
    </style>
    @php
        $width = (float) $media->width;
        $height = (float) $media->height;
        $isBillboard = (int) $media->category_id === 1;

        $panels = collect($media->panels ?? []);

        // A Bus Shelter is measured panel by panel, so width and height are
        // null on the record and printing them read "0.00 x 0.00 ft". Its size
        // is area_auto, the total its panels add up to.
        $hasFaceSize = $width > 0 && $height > 0;
        $sqft = $hasFaceSize ? $width * $height : (float) ($media->area_auto ?? 0);

        /* Which category this is, read off its NAME rather than its id.

           There was a $CATEGORY map of hardcoded ids here and it had drifted
           from the table: it called id 5 'OFFICE' when id 5 is Bus Shelter, so
           every bus shelter rendered the office branding fields — "Building
           Name: -" and "Branding Type: -" — and none of its own. Ids are the
           admin's to change; the name is what the rest of the app matches on
           (@see MediaCode, MediaManagementController), so match it here too. */
        $catSlug = \Illuminate\Support\Str::slug($media->category_name ?? '');
        $isCat = fn(string $fragment) => str_contains($catSlug, $fragment);
    @endphp
    <div class="container-fluid about-banner-img g-0">
        <div class="row">
            <!-- Desktop Image -->
            <div class="col-md-12 d-none d-md-block">
                <img src="{{ asset('assets/img/media_desk.png') }}" alt="Media banner" class="img-fluid w-100">
            </div>

            <!-- Mobile Image -->
            <div class="col-md-12 d-block d-md-none">
                <img src="{{ asset('assets/img/media_mobile.png') }}" alt="Media banner" class="img-fluid w-100">
            </div>
        </div>
    </div>
    <div class="container-fluid about-banner-img g-0">
        <!-- <div class="row">
                                                                                                                                                                                                               </div> -->
        {{-- ================= MEDIA DETAILS ================= --}}
        <div id="media-details" class="mt-150 mb-80">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 p-3">
                            {{-- MAIN IMAGE --}}
                            @php
                                $mainImage = $media->images->first();
                            @endphp
                            <div class="media-main mb-3" onmousemove="zoomMedia(event,this)" onmouseleave="resetZoom(this)">
                                <img class="main-media-image" id="mainMediaImage"
                                    src="{{ $mainImage ? config('fileConstants.IMAGE_VIEW') . $mainImage->images : asset('assets/img/no-image.jpg') }}">
                            </div>
                            {{-- THUMBNAILS --}}
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
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 p-4">

                            <h3 class="fw-bold mb-2 detail-title">
                                {{ ucwords($media->media_title ?: $media->category_name ?? '') }}
                                {{ ucwords($media->area_name ?? '') }}
                            </h3>

                            @if (!empty($media->hoarding_code))
                                <p class="mb-2">
                                    <span class="badge"
                                        style="background:#F97316;color:#FFFFFF;font-size:13px;font-weight:600;letter-spacing:.5px;">
                                        {{ $media->hoarding_code }}
                                    </span>
                                </p>
                            @endif

                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt text-danger"></i>
                                @if (!empty($media->address))
                                    {{ $media->address }}, {{ $media->city_name }}
                                @else
                                    {{ $media->city_name }}
                                @endif
                            </p>
                            <hr>
                            <div class="row">

                                {{-- ================= COMMON FIELDS ================= --}}

                                <div class="col-6 mb-2">
                                    <strong>Category Media Type:</strong> {{ $media->category_name }}
                                </div>
                                @if (!empty($media->facing))
                                    <div class="col-6 mb-2">
                                        <strong>Facing:</strong> {{ $media->facing }}
                                    </div>
                                @endif

                                @if (!empty($media->area_type))
                                    <div class="col-6 mb-2">
                                        <strong>Area Type:</strong> {{ ucfirst($media->area_type) }}
                                    </div>
                                @endif

                                @if ($hasFaceSize)
                                    <div class="col-6 mb-2">
                                        <strong>Size:</strong>
                                        {{ number_format($width, 2) }} x {{ number_format($height, 2) }} ft
                                    </div>
                                @endif

                                {{-- Panelled media carry their total in the table
                                     below, so it is not also stated here. --}}
                                @if ($panels->isEmpty())
                                    <div class="col-6 mb-2">
                                        <strong>Total Area:</strong>
                                        {{ number_format($sqft, 2) }} SQFT
                                    </div>
                                @endif

                                {{-- BUS SHELTER — a table rather than a run-on line.
                                     Three panels, each with a size, a count and the
                                     area it contributes, is a small grid of numbers;
                                     strung inline it wrapped mid-panel and the total
                                     above could not be followed back to its parts. --}}
                                @if ($panels->isNotEmpty())
                                    <div class="col-12 mb-2">
                                        <strong class="d-block mb-2">Panel Sizes</strong>

                                        <div class="table-responsive">
                                            <table class="panel-table">
                                                <thead>
                                                    <tr>
                                                        <th>Panel</th>
                                                        <th>Size (ft)</th>
                                                        <th class="text-center">Qty</th>
                                                        <th class="text-end">Area (sq ft)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $panelTotalQty = 0; @endphp
                                                    @foreach ($panels as $panel)
                                                        @php
                                                            // Rows written before the quantity column
                                                            // existed carry none and each stand for a
                                                            // single board.
                                                            $qty  = max(1, (int) ($panel->quantity ?? 1));
                                                            $face = (float) $panel->width * (float) $panel->height;
                                                            $panelTotalQty += $qty;
                                                        @endphp
                                                        <tr>
                                                            <td>{{ \App\Models\MediaLocationSize::POSITIONS[$panel->position] ?? ucfirst($panel->position) }}</td>
                                                            <td>{{ number_format((float) $panel->width, 2) }} × {{ number_format((float) $panel->height, 2) }}</td>
                                                            <td class="text-center">{{ $qty }}</td>
                                                            <td class="text-end">{{ number_format($face * $qty, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="2">Total</td>
                                                        <td class="text-center">{{ $panelTotalQty }}</td>
                                                        <td class="text-end">{{ number_format($sqft, 2) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                @if ($isCat('bus-shelter') && !empty($media->illumination_name))
                                    <div class="col-6 mb-2">
                                        <strong>Illumination:</strong> {{ $media->illumination_name }}
                                    </div>
                                @endif

                                {{-- ================= CATEGORY SPECIFIC FIELDS ================= --}}

                                {{-- WALL PAINTING --}}
                                @if ($isCat('wall-painting'))
                                    @if (!empty($media->address))
                                        <div class="col-12 mb-2">
                                            <strong>Address:</strong> {{ $media->address }}
                                        </div>
                                    @endif
                                @endif

                                {{-- AIRPORT BRANDING --}}
                                @if ($isCat('airport'))
                                    <div class="col-6 mb-2">
                                        <strong>Airport:</strong> {{ $media->airport_name ?? '-' }}
                                    </div>
                                    <div class="col-6 mb-2">
                                        <strong>Zone:</strong> {{ $media->zone_type ?? '-' }}
                                    </div>
                                    <div class="col-6 mb-2">
                                        <strong>Media Type:</strong> {{ $media->media_type ?? '-' }}
                                    </div>
                                @endif

                                {{-- TRANSIT MEDIA --}}
                                @if ($isCat('transit') || $isCat('transmit'))
                                    <div class="col-6 mb-2">
                                        <strong>Transit Type:</strong> {{ $media->transit_type ?? '-' }}
                                    </div>
                                    <div class="col-6 mb-2">
                                        <strong>Branding Type:</strong> {{ $media->branding_type ?? '-' }}
                                    </div>
                                    <div class="col-6 mb-2">
                                        <strong>Vehicle Count:</strong> {{ $media->vehicle_count ?? '-' }}
                                    </div>
                                @endif

                                {{-- OFFICE BRANDING — matched on the name like the rest.
                                     This used to test id 5, which is Bus Shelter, so it was
                                     the block putting "Building Name: -" and
                                     "Branding Type: -" on every shelter. --}}
                                @if ($isCat('office'))
                                    <div class="col-6 mb-2">
                                        <strong>Building Name:</strong> {{ $media->building_name ?? '-' }}
                                    </div>
                                    <div class="col-6 mb-2">
                                        <strong>Branding Type:</strong> {{ $media->wall_length ?? '-' }}
                                    </div>
                                @endif

                                {{-- WALL WRAP --}}
                                @if ($isCat('wall-wrap'))
                                    @if (!empty($media->address))
                                        <div class="col-12 mb-2">
                                            <strong>Location:</strong> {{ $media->address }}
                                        </div>
                                    @endif
                                @endif

                                {{-- WALL WRAP --}}
                                @if ($isCat('mall'))
                                    <div class="d-flex row">
                                        <div class="col-6 mb-2">
                                            <strong>Mall Name:</strong> {{ $media->mall_name }}
                                        </div>

                                        <div class="col-6 mb-2">
                                            <strong>Media Format:</strong> {{ $media->media_format }}
                                        </div>
                                    </div>
                                @endif


                                {{-- ================= PRICE (ALWAYS LAST) ================= --}}

                                <div class="col-12 mt-2">
                                    <span class="price">₹ {{ number_format($media->price, 2) }}</span>
                                    <span class="text-muted">/ month</span>

                                    @if ($isBillboard)
                                    @endif
                                </div>

                            </div>

                            <hr>



                            @if ((int) $media->category_id === 1)
                                {{-- CALENDAR --}}
                                <h6 class="fw-bold mt-1">Select Booking Dates</h6>

                                <form method="POST" action="{{ route('cart.add.with.date') }}" id="addToCartForm"
                                    style="overflow: visible;">

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
                            @else
                                {{-- CONTACT US (NON-BILLBOARD) --}}
                                <a href="{{ route('contact.create', ['media' => base64_encode($media->id)]) }}#contact-form"
                                    class="add-to-cart-btn mt-4 details-contact-us">
                                    <i class="fas fa-envelope"></i> Contact Us
                                </a>

                            @endif

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
