@extends('website.layout')

@section('title', 'Contact Us')

@section('content')


<!-- breadcrumb-section -->

<div class="container-fluid about-banner-img g-0">
    <div class="row">
        <!-- Desktop Image -->
        <div class="col-md-12 d-none d-md-block">
            <img src="{{ asset('assets/img/contactus1.png') }}" alt="About Banner" class="img-fluid">
        </div>

        <!-- Mobile Image -->
        <div class="col-md-12 d-block d-md-none">
            <img src="{{ asset('assets/img/contactusmobileview.png') }}" alt="About Banner" class="img-fluid">
        </div>
    </div>
</div>
<!-- end breadcrumb section -->

<!-- contact form -->
<div id="contact-form" class="contact-from-section conatact-top conatact-bottom">
    <div class="container">
        <div class="row contact-modern-wrapper">

            <div class="col-lg-8 mb-5 mb-lg-0">
                <div class="contact-card light-card">
                    <div class="form-title">
                        <h2>Send us a message</h2>
                        <p>Do you have a question, a concern, or need help choosing the right media option? Feel free to
                            reach out — our team is always happy to help.</p>
                    </div>

                    {{-- Success Message --}}
                    @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="contact-form">
                        <form method="POST" id="contactForm" action="{{ route('contact.store') }}" novalidate>
                            @csrf
                            {{-- MEDIA ID --}}
                            <input type="hidden" name="media_id" value="{{ $mediaId ?? '' }}">
                            <!-- ROW 1 : NAME + EMAIL -->
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <input type="text" class="form-control" placeholder="Full Name" name="full_name"
                                        value="{{ old('full_name') }}">
                                    @error('full_name')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <input type="email" class="form-control" placeholder="Email" name="email"
                                        value="{{ old('email') }}">
                                    @error('email')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <input type="tel" class="form-control" placeholder="Mobile" name="mobile_no"
                                        value="{{ old('mobile_no') }}">
                                    @error('mobile_no')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- ROW 2 : MOBILE + ADDRESS -->
                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <textarea name="address" class="form-control" rows="5" placeholder="Address" maxlength="200">{{ old('address') }}</textarea>
                                    <small class="text-muted" id="addressCounter">0 / 200</small>
                                    @error('address')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <textarea name="remark" class="form-control" rows="5" placeholder="Requirements/Specifications" maxlength="200">{{ old('remark') }}</textarea>
                                    <small class="text-muted" id="remarkCounter">0 / 300</small>
                                    @error('remark')
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <!-- SUBMIT -->
                            {{-- <div class="row d-flex justify-content-end ">
                                <div class="col-6">
                                    <input type="submit" value="Submit" class="boxed-btn">
                                </div>

                            </div> --}}
                            <div class="row d-flex justify-content-end">
                                <div class="col-md-6">
                                    <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}">
                                    </div>
                                    @error('g-recaptcha-response')
                                        <span class="text-danger"
                                            style="font-size: 14px;">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6 d-flex justify-content-end">
                                    <input type="submit" value="Submit" class="boxed-btn">
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE (UNCHANGED) -->
            <div class="col-lg-4">
                <div class="contact-card dark-card">
                    <div class="contact-form-wrap">
                        <div class="contact-form-box">
                            <h4><i class="fas fa-map"></i> Shop Address</h4>
                            <p>Brand Image Media Pvt Ltd,
                                Office No-4, 1st Floor,
                                Sadashiv Motkari Sankul,
                                Sadashiv Nagar,Opp.Sagar
                                Sweet, Nashik - 422009.</p>
                        </div>

                        <div class="contact-form-box">
                            <h4><i class="far fa-clock"></i> Shop Hours</h4>
                            <p>MON - FRIDAY: 8 to 9 PM <br> SAT - SUN: 10 to 8 PM</p>
                        </div>

                        <div class="contact-form-box">
                            <h4><i class="fas fa-address-book"></i> Contact</h4>
                            <p class="contact-us-page-detail">
                                Phone:
                                <a href="tel:+917770009506">+91 777 000 9506</a>
                                <br>
                                Email:
                                <a href="mailto:brandimage@gmail.com">brandimage@gmail.com</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- end contact form -->


<!-- find our location -->
<div class="find-location blue-bg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <p> <i class="fas fa-map-marker-alt"></i> Find Our Location</p>
            </div>
        </div>
    </div>
</div>
<!-- end find our location -->

<!-- google map section -->
<div class="embed-responsive embed-responsive-21by9">
    {{-- <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d26432.42324808999!2d-118.34398767954286!3d34.09378509738966!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80c2bf07045279bf%3A0xf67a9a6797bdfae4!2sHollywood%2C%20Los%20Angeles%2C%20CA%2C%20USA!5e0!3m2!1sen!2sbd!4v1576846473265!5m2!1sen!2sbd" width="100%" height="450" frameborder="0" style="border:0;" allowfullscreen="" class="embed-responsive-item"></iframe> --}}
    <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3749.6197830459505!2d73.77288857500191!3d19.982486081417772!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bddeb2094f5d9ff%3A0x57bf9c97dbf22492!2sBrand%20Image%20Media%20Pvt%20Ltd%20%7C%20Outdoor%20Advertising%20Agency!5e0!3m2!1sen!2sin!4v1766986988381!5m2!1sen!2sin"
        width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"
        referrerpolicy="no-referrer-when-downgrade" class="embed-responsive-item"></iframe>
</div>
<!-- end google map section -->

{{-- <script src="https://www.google.com/recaptcha/api.js" async defer></script> --}}

@section('scripts')

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<script>
$(document).ready(function () {

    const nameRegex   = /^[A-Za-z\s]+$/;
    const mobileRegex = /^[6-9][0-9]{9}$/;
    const emailRegex  = /^[^\s@]+@[a-zA-Z]+\.[a-zA-Z]{2,}$/;

    /* ================= INPUT RESTRICTIONS ================= */
    $('input[name="full_name"]').on('input', function () {
        this.value = this.value.replace(/[^A-Za-z\s]/g, '');
        clearError($(this));
    });

    $('input[name="mobile_no"]').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
        clearError($(this));
    });

    $('input[name="email"], textarea').on('input', function () {
        clearError($(this));
    });

    /* ================= COUNTERS ================= */
    // $('textarea').each(function () {
    //     const counter = $(this).attr('name') === 'address'
    //         ? $('#addressCounter')
    //         : $('#remarkCounter');

    //     $(this).on('input', function () {
    //         counter.text(`${this.value.length} / 200`);
    //     });
    // });
    $('textarea').each(function () {

        let maxLimit = 200;
        let counter;

        if ($(this).attr('name') === 'remark') {
            maxLimit = 300;
            counter = $('#remarkCounter');
        } else if ($(this).attr('name') === 'address') {
            maxLimit = 200;
            counter = $('#addressCounter');
        }

        // Set maxlength attribute dynamically
        $(this).attr('maxlength', maxLimit);

        $(this).on('input', function () {

            // Stop extra typing (safety)
            if (this.value.length > maxLimit) {
                this.value = this.value.substring(0, maxLimit);
            }

            counter.text(`${this.value.length} / ${maxLimit}`);
        });
    });

    /* ================= CLEAR ERROR ================= */
    function clearError(el) {
        el.removeClass('is-invalid');
        el.next('.text-danger').remove();
    }

    /* ================= SUBMIT ================= */
    $('form').on('submit', function (e) {

        e.preventDefault(); // IMPORTANT

        let valid = true;
        $('.text-danger').remove();
        $('.is-invalid').removeClass('is-invalid');

        function error(el, msg) {
            el.addClass('is-invalid');
            el.after(`<small class="text-danger d-block">${msg}</small>`);
            valid = false;
        }

        const name    = $('input[name="full_name"]');
        const email   = $('input[name="email"]');
        const mobile  = $('input[name="mobile_no"]');
        const address = $('textarea[name="address"]');
        const remark  = $('textarea[name="remark"]');

        if (!name.val()) error(name, 'Full name is required');
        else if (!nameRegex.test(name.val())) error(name, 'Only letters allowed');

        if (!email.val()) error(email, 'Email is required');
        else if (!emailRegex.test(email.val())) error(email, 'Enter valid email (example@gmail.com)');

        if (!mobile.val()) error(mobile, 'Mobile number is required');
        else if (!mobileRegex.test(mobile.val()))
            error(mobile, '10 digits & start with 6,7,8 or 9');

        if (!address.val()) error(address, 'Address is required');
        if (!remark.val()) error(remark, 'Requirements are required');

        /* ===== reCAPTCHA ===== */
        if (typeof grecaptcha !== 'undefined') {
            if (grecaptcha.getResponse().length === 0) {
                $('.g-recaptcha').after(
                    `<small class="text-danger d-block mt-1">
                        Please verify that you are not a robot
                    </small>`
                );
                valid = false;
            }
        }

        if (valid) {
            this.submit(); // ONLY HERE backend call
        }
    });
});
</script>

@endsection
