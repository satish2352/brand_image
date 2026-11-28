@extends('website.layout')

@section('title', 'Contact Us')

@section('content')
    {{-- ================= CONTACT ================= --}}
    {{-- The form, its field names, the .mb-3 / .error-space pairing the
         validation script walks, the character counters, the captcha holder and
         #submitBtn are all unchanged — only the layout around them is new. --}}
    <section class="bi-contact">
        <div class="container">
            <div class="row bi-contact-row">

                {{-- LEFT: the pitch and the ways to reach us --}}
                <div class="col-lg-5 bi-contact-info">
                    <span class="bi-contact-eyebrow"><span aria-hidden="true"></span> Get in touch</span>

                    <h1 class="bi-contact-title">
                        Let&rsquo;s Create <span class="accent">Something Great</span> Together
                    </h1>

                    <p class="bi-contact-lead">
                        Have a question, feedback, or a new project in mind? We&rsquo;d love to hear
                        from you. Reach out to us and our team will get back to you as soon as possible.
                    </p>

                    <!-- <ul class="bi-contact-list">
                        <li>
                            <span class="bi-contact-ico"><i class="bi bi-telephone-fill" aria-hidden="true"></i></span>
                            <div>
                                <span class="bi-contact-k">Phone</span>
                                <a class="bi-contact-v" href="tel:+917770009506">+91 777 000 9506</a>
                                <span class="bi-contact-note">Mon - Fri: 9 to 8 PM &middot; Sat - Sun: 10 to 7 PM</span>
                            </div>
                        </li>
                        <li>
                            <span class="bi-contact-ico"><i class="bi bi-envelope-fill" aria-hidden="true"></i></span>
                            <div>
                                <span class="bi-contact-k">Email</span>
                                <a class="bi-contact-v" href="mailto:sales@brand-image.co.in">sales@brand-image.co.in</a>
                                <span class="bi-contact-note">We reply within 24 hours</span>
                            </div>
                        </li>
                        <li>
                            <span class="bi-contact-ico"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i></span>
                            <div>
                                <span class="bi-contact-k">Our Office</span>
                                <a class="bi-contact-v bi-contact-addr"
                                    href="https://www.google.com/maps/search/?api=1&query=Brand+Image+Media+Pvt+Ltd+Sadashiv+Nagar+Nashik+422009"
                                    target="_blank" rel="noopener">
                                    Brand Adda Media Pvt Ltd, Office No-4, 1st Floor,<br>
                                    Sadashiv Motkari Sankul, Sadashiv Nagar,<br>
                                    Opp. Sagar Sweet, Nashik - 422009.
                                </a>
                            </div>
                        </li>
                    </ul> -->
                </div>

                {{-- RIGHT: the form --}}
                <div class="col-lg-7">
                    <div class="bi-contact-card">
                        <span class="bi-contact-eyebrow dark"> Drop us a
                            message</span>
                        <h2 class="bi-contact-h2">Contact Us</h2>
                        <p class="bi-contact-sub">Fill out the form below and we&rsquo;ll get back to you shortly.</p>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="contact-form">
                            <form method="POST" id="contactForm" action="{{ route('contact.store') }}" novalidate>
                                @csrf

                                <input type="hidden" name="media_id" value="{{ $mediaId ?? '' }}">

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="bi-field">
                                            <i class="bi bi-person" aria-hidden="true"></i>
                                            <input type="text" class="form-control" placeholder="Full Name *" name="full_name">
                                        </div>
                                        <div class="error-space"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="bi-field">
                                            <i class="bi bi-envelope" aria-hidden="true"></i>
                                            <input type="email" class="form-control" placeholder="Email *" name="email">
                                        </div>
                                        <div class="error-space"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="bi-field">
                                            <i class="bi bi-telephone" aria-hidden="true"></i>
                                            <input type="tel" class="form-control" placeholder="Mobile *" name="mobile_no">
                                        </div>
                                        <div class="error-space"></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="bi-field bi-field-area">
                                            <i class="bi bi-building" aria-hidden="true"></i>
                                            <textarea name="address" class="form-control" rows="5" placeholder="Address *"></textarea>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <div class="error-space"></div>
                                            <small class="text-muted" id="addressCounter">0 / 200</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="bi-field bi-field-area">
                                            <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                                            <textarea name="remark" class="form-control" rows="5" placeholder="Requirements *"></textarea>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <div class="error-space"></div>
                                            <small class="text-muted" id="remarkCounter">0 / 300</small>
                                        </div>
                                    </div>
                                </div>


                                {{-- Captcha first, then the reassurance strip and the submit. --}}
                                <div class="bi-contact-captcha">
                                    {{-- <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}">
                            </div> --}}
                                    <div id="contactCaptcha"></div>
                                </div>

                                <div class="bi-contact-foot">
                                <ul class="bi-contact-trust">
                                    <li>
                                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                                        <strong>Quick Response</strong>
                                        <span>We reply within 24 hours</span>
                                    </li>
                                    <li>
                                        <i class="bi bi-people" aria-hidden="true"></i>
                                        <strong>Expert Team</strong>
                                        <span>Get support from our experienced team</span>
                                    </li>
                                    <li>
                                        <i class="bi bi-lock" aria-hidden="true"></i>
                                        <strong>Your Privacy Matters</strong>
                                        <span>Your information is safe with us</span>
                                    </li>
                                </ul>

                                    <div class="bi-contact-send">
                                        <input type="submit" id="submitBtn" value="Submit" class="boxed-btn">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    {{-- end contact --}}


    <!-- find our location -->
    <!-- <div class="find-location blue-bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <p> <i class="fas fa-map-marker-alt"></i> Find Our Location</p>
                </div>
            </div>
        </div>
    </div> -->
    <!-- end find our location -->

    <!-- google map section -->
    <!-- <div class="embed-responsive embed-responsive-21by9">
        {{-- <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d26432.42324808999!2d-118.34398767954286!3d34.09378509738966!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80c2bf07045279bf%3A0xf67a9a6797bdfae4!2sHollywood%2C%20Los%20Angeles%2C%20CA%2C%20USA!5e0!3m2!1sen!2sbd!4v1576846473265!5m2!1sen!2sbd" width="100%" height="450" frameborder="0" style="border:0;" allowfullscreen="" class="embed-responsive-item"></iframe> --}}
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3749.6197830459505!2d73.77288857500191!3d19.982486081417772!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bddeb2094f5d9ff%3A0x57bf9c97dbf22492!2sBrand%20Image%20Media%20Pvt%20Ltd%20%7C%20Outdoor%20Advertising%20Agency!5e0!3m2!1sen!2sin!4v1766986988381!5m2!1sen!2sin"
            width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade" class="embed-responsive-item"></iframe>
    </div> -->
    <!-- end google map section -->

    {{-- <script src="https://www.google.com/recaptcha/api.js" async defer></script> --}}

@section('scripts')

    <script>
        let contactCaptchaWidget = null;

        // Read through config, not env(): once 'php artisan config:cache' has
        // been run — which production normally does — env() returns null
        // outside config files, and the widget would silently stop rendering.
        const contactCaptchaSiteKey = @json(config('services.recaptcha.site'));

        function onloadCallback() {
            // grecaptcha.render() throws 'Missing required parameters: sitekey'
            // when the key is blank, which aborts the rest of this callback.
            if (!contactCaptchaSiteKey) {
                console.warn('reCAPTCHA site key is not configured — set RECAPTCHA_SITE_KEY in .env');
                return;
            }
            contactCaptchaWidget = grecaptcha.render('contactCaptcha', {
                'sitekey': contactCaptchaSiteKey
            });
        }
    </script>

    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>


    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <script>
        $(document).ready(function() {

            const nameRegex = /^[A-Za-z\s]+$/;
            const mobileRegex = /^[6-9][0-9]{9}$/;
            const emailRegex = /^[^\s@]+@[a-zA-Z]+\.[a-zA-Z]{2,}$/;

            /* ================= INPUT RESTRICTIONS ================= */
            $('input[name="full_name"]').on('input', function() {
                this.value = this.value.replace(/[^A-Za-z\s]/g, '');
                clearError($(this));
            });

            $('input[name="mobile_no"]').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
                clearError($(this));
            });

            $('input[name="email"], textarea').on('input', function() {
                clearError($(this));
            });
            $('textarea').each(function() {

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

                $(this).on('input', function() {

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
                el.closest('.mb-3').find('.error-space').html('');
            }

            /* ================= SUBMIT ================= */
            $('form').on('submit', function(e) {

                e.preventDefault(); // IMPORTANT

                let valid = true;
                const submitBtn = $('#submitBtn');
                $('.text-danger').remove();
                $('.is-invalid').removeClass('is-invalid');

                function error(el, msg) {
                    el.addClass('is-invalid');
                    el.closest('.mb-3')
                        .find('.error-space')
                        .html(`<small class="text-danger">${msg}</small>`);
                    valid = false;
                }

                const name = $('input[name="full_name"]');
                const email = $('input[name="email"]');
                const mobile = $('input[name="mobile_no"]');
                const address = $('textarea[name="address"]');
                const remark = $('textarea[name="remark"]');

                if (!name.val()) error(name, 'Full name is required');
                else if (!nameRegex.test(name.val())) error(name, 'Only letters allowed');

                if (!email.val()) error(email, 'Email is required');
                else if (!emailRegex.test(email.val())) error(email,
                    'Enter valid email (example@gmail.com)');

                if (!mobile.val()) error(mobile, 'Mobile number is required');
                else if (!mobileRegex.test(mobile.val()))
                    error(mobile, '10 digits & start with 6,7,8 or 9');

                if (!address.val()) error(address, 'Address is required');
                if (!remark.val()) error(remark, 'Requirements are required');

                /* ===== reCAPTCHA ===== */
                // Only enforce it when a widget actually rendered — otherwise
                // getResponse(undefined) throws and the form can never submit.
                if (typeof grecaptcha !== 'undefined' && contactCaptchaWidget !== null &&
                    window.location.hostname !== 'localhost') {
                    if (grecaptcha.getResponse(contactCaptchaWidget).length === 0) {
                        $('#contactCaptcha').after(`
                            <small class="text-danger d-block mt-1">
                                Please verify that you are not a robot
                            </small>
                        `);
                        valid = false;
                    }
                }

                if (valid) {
                    // BUTTON DISABLE + TEXT CHANGE
                    submitBtn
                        .val('Submitting...')
                        .prop('disabled', true)
                        .css('cursor', 'not-allowed');

                    this.submit(); // backend call
                }
            });
        });
    </script>

@endsection
@endsection
