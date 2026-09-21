@extends('website.layout')

@section('title', 'Share Your Requirement')

@section('content')
    {{-- No hero banner here. This page is reached by someone who has just
         been told their session ended and asked to describe what they need —
         a full-width billboard photo above the form only pushes it below the
         fold and delays the one thing they came to do. --}}
    <section class="req-section">
        <div class="container">

            <div class="req-head">
                <span class="bi-eyebrow">Tell us what you need</span>
                <h1>Share Your Requirement</h1>
                <p>
                    Give us the brief and our media team will put a plan together for you —
                    locations, availability and pricing, without you having to search for it.
                </p>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show req-alert" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- A summary at the top: on a form this tall, a field that failed
                 three screens down is otherwise invisible after the redirect. --}}
            @if ($errors->any())
                <div class="alert alert-danger req-alert" role="alert">
                    Please correct the {{ $errors->count() }} highlighted
                    {{ Str::plural('field', $errors->count()) }} below and submit again.
                </div>
            @endif

            <div class="req-card">
                {{-- novalidate: the browser's own bubbles are one-at-a-time and
                     unstyled — the script below shows every problem at once. --}}
                <form method="POST" id="requirementForm" action="{{ route('website.requirement.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="source" value="{{ $source }}">

                    <h5 class="req-legend">Your details</h5>
                    <div class="row g-3">
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="full_name">Full Name <span class="req-star">*</span></label>
                            <input type="text" id="full_name" name="full_name"
                                class="form-control @error('full_name') is-invalid @enderror" maxlength="150"
                                autocomplete="name" value="{{ old('full_name') }}" placeholder="Your name">
                            <div class="req-error-space">
                                @error('full_name') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="mobile_no">Mobile Number <span class="req-star">*</span></label>
                            <input type="tel" id="mobile_no" name="mobile_no"
                                class="form-control @error('mobile_no') is-invalid @enderror" maxlength="10"
                                inputmode="numeric" autocomplete="tel-national" value="{{ old('mobile_no') }}"
                                placeholder="10-digit mobile number">
                            <div class="req-error-space">
                                @error('mobile_no') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" id="email" name="email"
                                class="form-control @error('email') is-invalid @enderror" maxlength="150"
                                autocomplete="email" value="{{ old('email') }}" placeholder="you@company.com">
                            <div class="req-error-space">
                                @error('email') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <h5 class="req-legend">Campaign</h5>
                    <div class="row g-3">
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="campaign_name">Campaign Name</label>
                            <input type="text" id="campaign_name" name="campaign_name"
                                class="form-control @error('campaign_name') is-invalid @enderror" maxlength="150"
                                value="{{ old('campaign_name') }}" placeholder="e.g. Diwali launch">
                            <div class="req-error-space">
                                @error('campaign_name') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="city">City <span class="req-star">*</span></label>
                            <input type="text" id="city" name="city"
                                class="form-control @error('city') is-invalid @enderror" maxlength="100"
                                autocomplete="address-level2" value="{{ old('city') }}" placeholder="e.g. Nashik">
                            <div class="req-error-space">
                                @error('city') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="area_location">Area / Location <span class="req-star">*</span></label>
                            <input type="text" id="area_location" name="area_location"
                                class="form-control @error('area_location') is-invalid @enderror" maxlength="255"
                                value="{{ old('area_location') }}" placeholder="e.g. College Road, Gangapur Road">
                            <div class="req-error-space">
                                @error('area_location') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-4 req-field">
                            <label class="form-label" for="media_type">Media Type <span class="req-star">*</span></label>
                            <input type="text" id="media_type" name="media_type"
                                class="form-control @error('media_type') is-invalid @enderror" maxlength="150"
                                value="{{ old('media_type') }}" placeholder="e.g. Hoarding, Bus Shelter, Mall">
                            <div class="req-error-space">
                                @error('media_type') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="campaign_start_date">Campaign Start Date <span class="req-star">*</span></label>
                            <input type="date" id="campaign_start_date" name="campaign_start_date"
                                class="form-control @error('campaign_start_date') is-invalid @enderror"
                                min="{{ now()->toDateString() }}" value="{{ old('campaign_start_date') }}">
                            <div class="req-error-space">
                                @error('campaign_start_date') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="campaign_end_date">Campaign End Date <span class="req-star">*</span></label>
                            <input type="date" id="campaign_end_date" name="campaign_end_date"
                                class="form-control @error('campaign_end_date') is-invalid @enderror"
                                min="{{ old('campaign_start_date', now()->toDateString()) }}"
                                value="{{ old('campaign_end_date') }}">
                            <div class="req-error-space">
                                @error('campaign_end_date') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-4 req-field">
                            <label class="form-label" for="campaign_duration">Campaign Duration <span class="req-star">*</span></label>
                            <input type="text" id="campaign_duration" name="campaign_duration"
                                class="form-control @error('campaign_duration') is-invalid @enderror" maxlength="100"
                                value="{{ old('campaign_duration') }}" placeholder="e.g. 30 days / 2 months">
                            <div class="req-error-space">
                                @error('campaign_duration') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="required_media_count">Required Number of Media <span class="req-star">*</span></label>
                            <input type="text" id="required_media_count" name="required_media_count"
                                class="form-control @error('required_media_count') is-invalid @enderror" maxlength="4"
                                inputmode="numeric" value="{{ old('required_media_count') }}" placeholder="e.g. 8">
                            <div class="req-error-space">
                                @error('required_media_count') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="approx_budget">Approximate Budget</label>
                            <input type="text" id="approx_budget" name="approx_budget"
                                class="form-control @error('approx_budget') is-invalid @enderror" maxlength="9"
                                inputmode="numeric" value="{{ old('approx_budget') }}" placeholder="e.g. 200000">
                            <div class="req-error-space">
                                <span class="req-hint" id="budgetHint"></span>
                                @error('approx_budget') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <h5 class="req-legend">Preferences <span class="req-optional">optional</span></h5>
                    <div class="row g-3">
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="target_audience">Target Audience</label>
                            <input type="text" id="target_audience" name="target_audience"
                                class="form-control @error('target_audience') is-invalid @enderror" maxlength="255"
                                value="{{ old('target_audience') }}" placeholder="e.g. Young professionals">
                            <div class="req-error-space">
                                @error('target_audience') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="preferred_location">Preferred Location</label>
                            <input type="text" id="preferred_location" name="preferred_location"
                                class="form-control @error('preferred_location') is-invalid @enderror" maxlength="255"
                                value="{{ old('preferred_location') }}" placeholder="e.g. Near highway exits">
                            <div class="req-error-space">
                                @error('preferred_location') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4 req-field">
                            <label class="form-label" for="preferred_media_size">Preferred Media Size</label>
                            <input type="text" id="preferred_media_size" name="preferred_media_size"
                                class="form-control @error('preferred_media_size') is-invalid @enderror" maxlength="100"
                                value="{{ old('preferred_media_size') }}" placeholder="e.g. 40 x 20 ft">
                            <div class="req-error-space">
                                @error('preferred_media_size') <span class="req-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 req-field">
                            <label class="form-label" for="additional_comments">Additional Comments</label>
                            <textarea id="additional_comments" name="additional_comments"
                                class="form-control @error('additional_comments') is-invalid @enderror" rows="4"
                                maxlength="2000" placeholder="Anything else we should know?">{{ old('additional_comments') }}</textarea>
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="req-error-space flex-grow-1">
                                    @error('additional_comments') <span class="req-error">{{ $message }}</span> @enderror
                                </div>
                                <span class="req-counter" id="commentsCounter">0 / 2000</span>
                            </div>
                        </div>
                    </div>

                    <div class="req-actions">
                        {{-- Always rendered, exactly like the login dialog's own
                             box in the header — that one carries no config gate
                             either. Whether the answer is VERIFIED is still
                             services.recaptcha.enabled's call, server-side in
                             RequirementController via App\Support\Recaptcha.

                             It sits first in the actions row, filling the space
                             the two buttons leave on the left. --}}
                        <div class="req-captcha">
                            {{-- Not .g-recaptcha, for the reason the admin login
                                 box gives: that class is auto-rendered by the API
                                 on load and hands back no widget id, so with the
                                 header's own login widget also on this page
                                 grecaptcha.getResponse() would keep answering for
                                 whichever rendered first. Rendered explicitly in
                                 the script below so this form reads its own. --}}
                            <div id="reqCaptcha"></div>
                            <div class="req-error-space">
                                @error('g-recaptcha-response')
                                    <span class="req-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <a href="{{ url('/contact-us') }}" class="sa-btn sa-btn-ghost">
                            <i class="bi bi-headset" aria-hidden="true"></i> Contact Brand Adda Team
                        </a>
                        <button type="submit" class="sa-btn sa-btn-primary">
                            <i class="bi bi-send" aria-hidden="true"></i> Submit Requirement
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </section>
@endsection

@section('scripts')
    <script>
        /**
         * Client-side mirror of RequirementController's rules.
         *
         * Same checks, same wording, so a field that passes here is not
         * rejected by the server a page-load later. The server stays the
         * authority — this only saves the round trip.
         */
        $(function () {

            var $form = $('#requirementForm');
            if (!$form.length) return;

            // \p{L} to match the server's rule exactly — a name in Devanagari
            // is as valid here as one in Latin script.
            var NAME_RE   = /^\p{L}[\p{L}\s.'-]*$/u;
            var NAME_STRIP = /[^\p{L}\s.'-]/gu;
            var MOBILE_RE = /^[6-9][0-9]{9}$/;
            var EMAIL_RE  = /^[^\s@]+@[^\s@.]+(\.[^\s@.]+)+$/;

            var today = '{{ now()->toDateString() }}';

            /* ================= INPUT RESTRICTIONS =================
               Keep the wrong character out rather than complain about it
               afterwards. */
            function digitsOnly($el, max) {
                $el.on('input', function () {
                    this.value = this.value.replace(/\D/g, '').substring(0, max);
                });
            }

            digitsOnly($('#mobile_no'), 10);
            digitsOnly($('#required_media_count'), 4);
            digitsOnly($('#approx_budget'), 9);

            $('#full_name, #city').on('input', function () {
                this.value = this.value.replace(NAME_STRIP, '');
            });

            /* The end date can never precede the start, so the picker itself
               stops offering those days. */
            $('#campaign_start_date').on('change', function () {
                var $end = $('#campaign_end_date');
                $end.attr('min', this.value || today);
                if ($end.val() && this.value && $end.val() < this.value) {
                    $end.val('');
                }
            });

            /* ================= LIVE FEEDBACK ================= */
            var $comments = $('#additional_comments');
            var $counter  = $('#commentsCounter');

            function countComments() {
                $counter.text($comments.val().length + ' / 2000');
            }
            $comments.on('input', countComments);
            countComments();

            var $budget = $('#approx_budget');
            var $budgetHint = $('#budgetHint');

            /* Budgets here run to seven figures — echoing the amount in words
               is how someone catches a stray zero before we quote on it. */
            function showBudget() {
                var value = $budget.val();
                if (!$budgetHint.length || !value) {
                    $budgetHint.text('');
                    return;
                }
                $budgetHint.text('₹ ' + Number(value).toLocaleString('en-IN'));
            }
            $budget.on('input', showBudget);
            showBudget();

            /* ================= ERROR PLUMBING ================= */
            function clearError($el) {
                $el.removeClass('is-invalid');
                $el.closest('.req-field').find('.req-error-space .req-error').remove();
            }

            function setError($el, message) {
                clearError($el);
                $el.addClass('is-invalid');
                $el.closest('.req-field')
                    .find('.req-error-space')
                    .first()
                    .append($('<span class="req-error"></span>').text(message));
            }

            // A field stops being wrong the moment it is being fixed.
            $form.on('input change', '.form-control', function () {
                if ($(this).hasClass('is-invalid')) clearError($(this));
            });

            /* ================= RULES =================
               [selector, label, {…}] — checked in the order they appear on the
               form so the first error is also the topmost one. */
            var RULES = [
                ['#full_name',            'Full name',      { required: true, min: 3, re: NAME_RE, reMsg: 'Please enter a valid name — letters only.' }],
                ['#mobile_no',            'Mobile number',  { required: true, re: MOBILE_RE, reMsg: 'Enter a 10-digit mobile number starting with 6, 7, 8 or 9.' }],
                ['#email',                'Email',          { re: EMAIL_RE, reMsg: 'Enter a valid email address (e.g. name@company.com).' }],
                ['#campaign_name',        'Campaign name',  { min: 2 }],
                ['#city',                 'City',           { required: true, min: 2, re: NAME_RE, reMsg: 'Please enter a valid city name — letters only.' }],
                ['#area_location',        'Area / location',{ required: true, min: 3 }],
                ['#media_type',           'Media type',     { required: true, min: 3 }],
                ['#campaign_start_date',  'Start date',     { required: true, notBefore: today, notBeforeMsg: 'The start date cannot be in the past.' }],
                ['#campaign_end_date',    'End date',       { required: true }],
                ['#campaign_duration',    'Duration',       { required: true, min: 2 }],
                ['#required_media_count', 'Required number of media', { required: true, minNum: 1, numMsg: 'Enter how many media units you need, as a number.' }],
                ['#approx_budget',        'Budget',         { minNum: 1000, numMsg: 'Please enter a budget of at least ₹1,000.' }],
                ['#target_audience',      'Target audience',{ min: 2 }],
                ['#preferred_location',   'Preferred location', { min: 2 }],
                ['#preferred_media_size', 'Preferred media size', { min: 2 }]
            ];

            function validate() {
                var $first = null;

                function fail($el, message) {
                    setError($el, message);
                    if (!$first) $first = $el;
                }

                RULES.forEach(function (rule) {
                    var $el   = $(rule[0]);
                    var label = rule[1];
                    var opts  = rule[2];
                    var value = String($el.val() || "").trim();

                    if (!value) {
                        if (opts.required) fail($el, label + ' is required.');
                        return; // Blank and optional: nothing else to check.
                    }

                    if (opts.min && value.length < opts.min) {
                        fail($el, label + ' must be at least ' + opts.min + ' characters.');
                        return;
                    }

                    if (opts.re && !opts.re.test(value)) {
                        fail($el, opts.reMsg);
                        return;
                    }

                    if (opts.minNum && Number(value) < opts.minNum) {
                        fail($el, opts.numMsg);
                        return;
                    }

                    if (opts.notBefore && value < opts.notBefore) {
                        fail($el, opts.notBeforeMsg);
                    }
                });

                // Cross-field: only worth saying once both dates are readable.
                var start = $('#campaign_start_date').val();
                var end   = $('#campaign_end_date').val();

                if (start && end && end < start) {
                    fail($('#campaign_end_date'), 'The end date cannot be before the start date.');
                }

                return $first;
            }

            /* The captcha, rendered the way the admin login renders its own:
               explicitly, keeping the widget id, so this form reads its own
               answer rather than the first widget on the page. api.js is
               loaded async by the header, so it may not be here yet. */
            var reqCaptchaId = null;

            (function renderWhenReady(attempt) {
                if (!document.getElementById('reqCaptcha')) return;

                if (window.grecaptcha && typeof grecaptcha.render === 'function') {
                    reqCaptchaId = grecaptcha.render('reqCaptcha', {
                        sitekey: "{{ config('services.recaptcha.site') }}"
                    });
                } else if (attempt < 40) {
                    setTimeout(function () { renderWhenReady(attempt + 1); }, 150);
                }
            })(0);

            $form.on('submit', function (e) {
                var $first = validate();

                // The captcha is checked again on the server — this only saves
                // the round trip, and says what the server would have said.
                // getResponse takes the widget id, so it answers for THIS form
                // and not for the header's login box. A null id means the
                // widget has not rendered yet, so there is nothing to check
                // and the server decides.
                //
                // localhost is exempt, the same exemption the login dialog
                // makes: the keys are domain-bound, so a widget on a dev box
                // can never be satisfied and would block every local submit.
                var captchaRequired = window.location.hostname !== 'localhost'
                    && window.location.hostname !== '127.0.0.1';

                if (captchaRequired && reqCaptchaId !== null
                    && grecaptcha.getResponse(reqCaptchaId).length === 0) {
                    var $captcha = $('#reqCaptcha');
                    var $slot = $captcha.closest('.req-captcha').find('.req-error-space');
                    $slot.find('.req-error').remove();
                    $slot.append('<span class="req-error">Please verify that you are not a robot</span>');
                    if (!$first) $first = $captcha;
                }

                if (!$first) return;

                e.preventDefault();

                // Land the page on the problem, not at the top of a long form.
                $('html, body').animate({
                    scrollTop: $first.offset().top - 140
                }, 300, function () {
                    $first.trigger('focus');
                });
            });
        });
    </script>
@endsection
