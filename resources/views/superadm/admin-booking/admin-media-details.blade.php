@extends('superadm.layout.master')
@section('content')
    {{-- ================= FLATPICKR ================= --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        /* parent must be relative */
        .font-weight-admin {

            font-weight: 700 !important;
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
            color: #f28123;
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
            background: #f28123 !important;
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
            color: #f28123;
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

        /* ===== AVAILABLE DATE HOVER ===== */
        .flatpickr-day:not(.booked-date):not(.flatpickr-disabled):hover {
            background: #218838 !important;
            color: #fff !important;
        }

        .flatpickr-day.flatpickr-disabled {
            color: #0f0d0d4d !important;
        }

        /* ===== BOOKED DATE HOVER ===== */
        .flatpickr-day.booked-date:hover {
            background: #dc3545 !important;
            /* same as normal */
            color: #fff !important;
            cursor: not-allowed;
        }

        /* ===== DISABLED DATE (NO HOVER) ===== */
        .flatpickr-day.flatpickr-disabled:hover {
            /* background: transparent !important; */
            cursor: not-allowed;
        }
    </style>
    @php
        $width = (float) $media->width;
        $height = (float) $media->height;
        $sqft = $width * $height;
    @endphp
    {{-- ================= MEDIA DETAILS ================= --}}
    <div class="mt-150 mb-150">
        <div class="container-fluid">
            <div class="card shadow-sm border-0 p-4">
                <div class="row g-4">
                    {{-- ================= LEFT : IMAGE GALLERY ================= --}}
                    <div class="col-lg-5">
                        <h3 class="fw-bold mb-1">{{ $media->area_name }} {{ $media->facing }}</h3>
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt text-danger"></i>
                            {{ $media->area_name }}, {{ $media->city_name }}
                        </p>
                        <hr>
                        <div class="row ">
                            <div class="col-6 mb-2"><strong class="font-weight-admin">Category:</strong>
                                {{ $media->category_name }}</div>
                            <div class="col-6 mb-2"><strong class="font-weight-admin">Media Title:</strong>
                                {{ $media->media_title }}</div>
                            <div class="col-6 mb-2"><strong class="font-weight-admin">Facing:</strong> {{ $media->facing }}
                            </div>
                            <div class="col-6 mb-2"><strong class="font-weight-admin">Area Type:</strong>
                                {{ $media->area_type }}</div>
                            <div class="col-6 mb-2"><strong class="font-weight-admin">Illumination:</strong>
                                {{ $media->illumination_name }}</div>
                            <div class="col-6 mb-2">
                                <strong>Size:</strong>
                                {{ number_format($width, 2) }} × {{ number_format($height, 2) }} ft
                            </div>
                            <div class="col-6 mb-2">
                                <strong>Total Area:</strong>
                                {{ number_format($sqft, 2) }} SQFT
                            </div>
                            <div class="col-12 mb-2">
                                <strong>Address:</strong> {{ $media->address }}
                            </div>
                        </div>
                        <hr>
                        {{-- PRICE --}}
                        <div class="mb-3">
                            <span class="price">₹ {{ number_format($media->price, 2) }}</span>
                            <span class="text-muted"> / month</span>
                            <div class="per-day">₹ {{ number_format($media->per_day_price, 2) }} / day</div>
                        </div>
                    </div>
                    {{-- ================= RIGHT : DETAILS + CALENDAR ================= --}}
                    <div class="col-lg-7">
                        {{-- CALENDAR --}}
                        <form id="bookingForm" method="POST" action="{{ route('admin.booking.store') }}" novalidate>
                            {{-- <form method="POST" action="{{ route('admin.booking.store') }}"> --}}
                            @csrf
                            <input type="hidden" name="media_id" value="{{ base64_encode($media->id) }}">
                            <input type="hidden" name="from_date" id="from_date">
                            <input type="hidden" name="to_date" id="to_date">
                            <input type="hidden" name="total_days" id="total_days">
                            <input type="hidden" name="per_day_price" id="per_day_price">
                            <div class="mb-3">
                                <label>Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="signup_name"
                                    class="form-control @error('signup_name') is-invalid @enderror"
                                    value="{{ old('signup_name') }}">
                                @error('signup_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Email Id <span class="text-danger">*</span></label>
                                    <input type="email" name="signup_email"
                                        class="form-control @error('signup_email') is-invalid @enderror"
                                        value="{{ old('signup_email') }}">
                                    @error('signup_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Mobile Number <span class="text-danger">*</span></label>
                                    <input type="text" name="signup_mobile_number"
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
                            <button type="submit" class="add-to-cart-btn mt-4" id="addToCartBtn" disabled>
                                <i class="fas fa-shopping-cart"></i> Book
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        /* ================= REGEX ================= */
        const nameRegex = /^[A-Za-z\s]+$/;
        const mobileRegex = /^[6-9][0-9]{9}$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
        /* ================= LIVE INPUT RESTRICTION ================= */
        // Full Name → only letters & space
        $('input[name="signup_name"]').on('input', function() {
            this.value = this.value.replace(/[^A-Za-z\s]/g, '');
            clearError($(this));
        });
        // Mobile → only digits
        $('input[name="signup_mobile_number"]').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            clearError($(this));
        });
        // Email → clear error while typing
        $('input[name="signup_email"]').on('input', function() {
            clearError($(this));
        });
        /* ================= CLEAR ERROR ================= */
        function clearError(el) {
            el.removeClass('is-invalid');
            el.closest('.mb-3, .col-md-6')
                .find('.invalid-feedback')
                .remove();
        }
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
        document.addEventListener('DOMContentLoaded', function() {
            const bookedRanges = @json($bookedRanges ?? []);
            const pricePerDay = {{ (float) $media->per_day_price }};
            const MIN_DAYS = 15;
            const fromInput = document.getElementById('from_date');
            const toInput = document.getElementById('to_date');
            const addBtn = document.getElementById('addToCartBtn');
            const errorBox = document.getElementById('dateError');
            flatpickr("#booking_range", {
                mode: "range",
                minDate: "today",
                dateFormat: "Y-m-d",
                inline: true,
                static: true,
                showMonths: 2,
                //  Disable booked dates
                disable: bookedRanges.map(r => ({
                    from: r.from_date,
                    to: r.to_date
                })),
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    const date = fp.formatDate(dayElem.dateObj, "Y-m-d");
                    let isBooked = false;
                    bookedRanges.forEach(range => {
                        if (date >= range.from_date && date <= range.to_date) {
                            isBooked = true;
                        }
                    });
                    // 🔴 BOOKED DATE
                    if (isBooked) {
                        dayElem.classList.add('booked-date');
                        dayElem.setAttribute('title', 'Already Booked ❌');
                        dayElem.style.cursor = 'not-allowed';
                    }
                    // 🟢 AVAILABLE DATE
                    else if (!dayElem.classList.contains('flatpickr-disabled')) {
                        dayElem.setAttribute('title', 'Available ');
                        dayElem.style.cursor = 'pointer';
                    }
                },
                onChange: function(selectedDates) {
                    addBtn.disabled = true;
                    errorBox.classList.add('d-none');
                    if (selectedDates.length !== 2) return;
                    const start = selectedDates[0];
                    const end = selectedDates[1];
                    // Calculate difference days
                    // const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
                    const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
                    if (diffDays < MIN_DAYS) {
                        errorBox.innerText = `Minimum booking period is ${MIN_DAYS} days`;
                        errorBox.classList.remove('d-none');
                        document.getElementById('totalDays').innerText = 0;
                        document.getElementById('baseAmount').innerText = "0.00";
                        document.getElementById('gstAmount').innerText = "0.00";
                        document.getElementById('grandTotal').innerText = "0.00";
                        document.getElementById('total_amount').value = "";
                        document.getElementById('gst_amount').value = "";
                        document.getElementById('grand_total').value = "";
                        document.getElementById('total_days').value = diffDays;
                        document.getElementById('total_days').value = diffDays;
                        document.getElementById('per_day_price').value = "";
                        fromInput.value = "";
                        toInput.value = "";
                        addBtn.disabled = true;
                        return;
                    }
                    // ===========================
                    // MONTH WISE BILLING START
                    // ===========================
                    const monthlyPrice = {{ (float) $media->price }};
                    let totalAmount = 0;
                    let cursor = new Date(start);
                    while (cursor <= end) {
                        const year = cursor.getFullYear();
                        const month = cursor.getMonth(); // 0 index
                        const daysInMonth = new Date(year, month + 1, 0).getDate();
                        const perDayPrice = monthlyPrice / daysInMonth;
                        totalAmount += perDayPrice;
                        cursor.setDate(cursor.getDate() + 1);
                    }
                    const gstAmount = +(totalAmount * 0.18).toFixed(2);
                    const grandTotal = +(totalAmount + gstAmount).toFixed(2);
                    document.getElementById('totalDays').innerText = diffDays;
                    document.getElementById('baseAmount').innerText = totalAmount.toFixed(2);
                    document.getElementById('gstAmount').innerText = gstAmount.toFixed(2);
                    document.getElementById('grandTotal').innerText = grandTotal.toFixed(2);
                    document.getElementById('total_amount').value = totalAmount.toFixed(2);
                    document.getElementById('gst_amount').value = gstAmount.toFixed(2);
                    document.getElementById('grand_total').value = grandTotal.toFixed(2);
                    document.getElementById('total_days').value = diffDays;
                    document.getElementById('per_day_price').value = (totalAmount / diffDays).toFixed(
                        2);

                    fromInput.value = flatpickr.formatDate(start, "Y-m-d");
                    toInput.value = flatpickr.formatDate(end, "Y-m-d");

                    addBtn.disabled = false;
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Booking Successful 🎉',
                text: "{{ session('success') }}",
                confirmButtonText: 'OK',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('admin.booking.list-booking') }}";
                }
            });
        </script>
    @endif
    @if (session('error'))
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
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <script>
        $(document).ready(function() {

            /* ===== Custom Rules ===== */

            $.validator.addMethod("letterswithspace", function(value, element) {
                return this.optional(element) || /^[A-Za-z\s]+$/.test(value);
            }, "Only letters and spaces allowed");

            $.validator.addMethod("mobileIN", function(value, element) {
                return this.optional(element) || /^[6-9][0-9]{9}$/.test(value);
            }, "Enter a valid 10 digit mobile number");

            /* ===== Validation ===== */

            $("#bookingForm").validate({
                ignore: [],
                rules: {
                    signup_name: {
                        required: true,
                        letterswithspace: true
                    },
                    signup_email: {
                        required: true,
                        email: true
                    },
                    signup_mobile_number: {
                        required: true,
                        mobileIN: true
                    },
                    from_date: {
                        required: true
                    },
                    to_date: {
                        required: true
                    }
                },
                messages: {
                    signup_name: {
                        required: "Full name is required"
                    },
                    signup_email: {
                        required: "Email is required",
                        email: "Enter a valid email (example@mail.com)"
                    },
                    signup_mobile_number: {
                        required: "Mobile number is required"
                    },
                    from_date: {
                        required: "Please select booking dates"
                    },
                    to_date: {
                        required: "Please select booking dates"
                    }
                },
                errorElement: "div",
                errorClass: "invalid-feedback",
                highlight: function(element) {
                    $(element).addClass("is-invalid");
                },
                unhighlight: function(element) {
                    $(element).removeClass("is-invalid");
                },
                errorPlacement: function(error, element) {
                    element.closest(".mb-3, .col-md-6").append(error);
                },
                submitHandler: function(form) {

                    Swal.fire({
                        title: "Confirm Booking?",
                        text: "Do you want to book this media for selected dates?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#28a745",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, Book Now"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit(); //  FINAL SUBMIT
                        }
                    });

                }
            });

        });
    </script>
@endsection
