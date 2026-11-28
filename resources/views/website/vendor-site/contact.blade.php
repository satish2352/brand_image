@extends('website.vendor-site.layout')
@section('title', 'Contact · ' . $vendor->display_name)

@section('nav')
    @include('website.vendor-site.partials.nav', ['active' => 'contact'])
@endsection

@section('styles')
    <style>
        .vs-page-hero { background: var(--vs-brand); color: var(--vs-brand-text); padding: 56px 0 96px; }
        .vs-contact { border: 1px solid #E5E7EB; border-radius: 16px; overflow: hidden; background: #fff; box-shadow: 0 12px 32px rgba(15, 23, 42, .08); }
        .vs-contact-brand { padding: 24px 28px; display: flex; flex-wrap: wrap; align-items: center; gap: 18px; border-bottom: 1px solid #F1F5F9; }
        .vs-contact-actions { margin-left: auto; display: flex; flex-wrap: wrap; gap: 10px; }
        .vs-contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .vs-contact-item { display: flex; gap: 14px; align-items: flex-start; padding: 26px 28px; }
        .vs-contact-item + .vs-contact-item { border-left: 1px solid #F1F5F9; }
        .vs-contact-icon { width: 46px; height: 46px; flex: none; border-radius: 12px; background: #FFF7ED; color: #C2410C; display: grid; place-items: center; font-size: 18px; }
        .vs-contact-label { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .06em; }
        .vs-contact-value { display: block; font-size: 16px; font-weight: 700; margin-top: 4px; line-height: 1.5; text-decoration: none; }
        .vs-err { color: #B91C1C; font-size: 12px; font-weight: 600; margin-top: 6px; min-height: 0; }
        .vs-err:empty { display: none; }
        .vs-input.vs-invalid, .vs-input-group.vs-invalid { border-color: #DC2626 !important; box-shadow: 0 0 0 3px rgba(220, 38, 38, .12); }
        .vs-input.vs-valid, .vs-input-group.vs-valid { border-color: #16A34A !important; }
        .vs-input:focus { outline: none; border-color: #F97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, .18); }
        .vs-input-group { display: flex; align-items: stretch; border: 1px solid #CBD5E1; border-radius: 10px; overflow: hidden; background: #fff; }
        .vs-input-group:focus-within { border-color: #F97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, .18); }
        .vs-input-group .vs-prefix { display: grid; place-items: center; padding: 0 12px; background: #F1F5F9; color: #334155; font-weight: 700; font-size: 14px; border-right: 1px solid #CBD5E1; }
        .vs-input-group .vs-input { border: 0; border-radius: 0; box-shadow: none !important; letter-spacing: .04em; }
        @media (max-width: 640px) {
            .vs-page-hero { padding: 36px 0 80px; }
            .vs-contact-actions { margin-left: 0; }
            .vs-contact-item + .vs-contact-item { border-left: 0; border-top: 1px solid #F1F5F9; }
        }
        /* Call / WhatsApp / Email: round icon buttons with a tooltip. */
        .vs-contact-actions { gap: 12px !important; }
        .vs-icon-btn { width: 52px; height: 52px; border-radius: 50%; display: grid; place-items: center; font-size: 21px; text-decoration: none;
            color: #fff; transition: transform .15s, box-shadow .15s; }
        .vs-icon-btn:hover { transform: translateY(-2px); color: #fff; }
        .vs-icon-btn.is-call { background: #F97316; box-shadow: 0 8px 18px rgba(249,115,22,.3); }
        .vs-icon-btn.is-wa { background: #16A34A; box-shadow: 0 8px 18px rgba(22,163,74,.3); font-size: 25px; }
        .vs-icon-btn.is-mail { background: #0F172A; box-shadow: 0 8px 18px rgba(15,23,42,.25); }
        .vs-icon-btn:focus-visible { outline: 3px solid #FDBA74; outline-offset: 3px; }
    </style>
@endsection

@section('content')
    <section class="vs-page-hero">
        <div class="vs-wrap">
            <div style="font-size:13px;color:var(--vs-brand-muted)"><a href="{{ route('vendor-site.show', $vendor->slug) }}" style="color:var(--vs-brand-link);font-weight:700">Home</a> / Contact</div>
            <h1 style="margin:10px 0 8px;font-size:40px;font-weight:800">Contact {{ $vendor->display_name }}</h1>
            <p style="margin:0;font-size:16px;color:var(--vs-brand-muted);max-width:560px">Ask about any of our {{ $totals['media'] }} sites, their availability or rates. We usually reply the same day.</p>
        </div>
    </section>

    <main class="vs-wrap" style="margin-top:-56px;padding-bottom:64px">
        <div class="vs-contact">
            <div class="vs-contact-brand">
                <span class="vs-mono" style="width:64px;height:64px;font-size:22px">
                    @if ($vendor->logo_url)<img src="{{ $vendor->logo_url }}" alt="">@else{{ $vendor->initials }}@endif
                </span>
                <div>
                    <div style="font-size:22px;font-weight:800">{{ $vendor->display_name }}</div>
                    <div style="color:#475569;font-size:14px;margin-top:4px">Outdoor media · {{ $totals['media'] }} sites across {{ $totals['districts'] }} district{{ $totals['districts'] == 1 ? '' : 's' }}</div>
                </div>
                <div class="vs-contact-actions">
                    @if ($contact['tel'])
                        <a href="tel:{{ $contact['tel'] }}" class="vs-icon-btn is-call" title="Call {{ $vendor->display_name }}" aria-label="Call {{ $vendor->display_name }}"><i class="fa-solid fa-phone" aria-hidden="true"></i></a>
                    @endif
                    @if ($contact['whatsapp'])
                        <a href="https://wa.me/{{ $contact['whatsapp'] }}?text={{ rawurlencode('Hi ' . $vendor->display_name . ', I saw your media on Brand Adda and would like to know more.') }}"
                            target="_blank" rel="noopener" class="vs-icon-btn is-wa" title="Chat on WhatsApp" aria-label="Chat with {{ $vendor->display_name }} on WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
                    @endif
                    @if ($contact['email'])
                        <a href="mailto:{{ $contact['email'] }}?subject={{ rawurlencode('Media enquiry via Brand Adda') }}" class="vs-icon-btn is-mail" title="Send an email" aria-label="Email {{ $vendor->display_name }}"><i class="fa-solid fa-envelope" aria-hidden="true"></i></a>
                    @endif
                </div>
            </div>

            <div class="vs-contact-grid">
                @if ($contact['mobile'])
                    <div class="vs-contact-item">
                        <span class="vs-contact-icon"><i class="fa-solid fa-phone"></i></span>
                        <div><div class="vs-contact-label">Phone</div><a href="tel:{{ $contact['tel'] }}" class="vs-contact-value">{{ $contact['mobile'] }}</a></div>
                    </div>
                @endif
                @if ($contact['email'])
                    <div class="vs-contact-item">
                        <span class="vs-contact-icon"><i class="fa-regular fa-envelope"></i></span>
                        <div><div class="vs-contact-label">Email</div><a href="mailto:{{ $contact['email'] }}" class="vs-contact-value" style="word-break:break-all">{{ $contact['email'] }}</a></div>
                    </div>
                @endif
                @if ($contact['address'] || $contact['location'])
                    <div class="vs-contact-item">
                        <span class="vs-contact-icon"><i class="fa-solid fa-location-dot"></i></span>
                        <div>
                            <div class="vs-contact-label">Address</div>
                            <div class="vs-contact-value" style="color:#0F172A">{{ $contact['address'] }}@if ($contact['address'] && $contact['location'])<br>@endif{{ $contact['location'] }}</div>
                            <a href="{{ $contact['mapsUrl'] }}" target="_blank" rel="noopener" style="font-size:13px;font-weight:700">Get directions</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ================= ENQUIRY FORM ================= --}}
        <section id="enquiry" class="vs-contact" style="margin-top:28px;scroll-margin-top:20px">
            <div style="padding:24px 28px;border-bottom:1px solid #F1F5F9">
                <h2 style="margin:0;font-size:22px;font-weight:800">Send an enquiry</h2>
                <p style="margin:6px 0 0;color:#475569;font-size:14px">Tell us what you need and {{ $vendor->display_name }} will get back to you.</p>
            </div>

            @if (session('enquiry_sent'))
                <div style="padding:40px 28px;text-align:center">
                    <div style="width:64px;height:64px;margin:0 auto 14px;border-radius:999px;background:#DCFCE7;color:#166534;display:grid;place-items:center;font-size:28px"><i class="fa-solid fa-check"></i></div>
                    <h3 style="margin:0;font-size:20px;font-weight:800">Thank you! Your enquiry has been sent.</h3>
                    <p style="margin:8px 0 18px;color:#475569">{{ $vendor->display_name }} will contact you soon on the phone or email you gave.</p>
                    {{-- A new address (?new=1), so the browser reloads the page and shows an empty form; the same URL with only #enquiry would just scroll. --}}
                    <a href="{{ route('vendor-site.contact', [$vendor->slug, 'new' => 1]) }}#enquiry" class="vs-btn vs-btn-g">Send another enquiry</a>
                </div>
            @else
                <form method="POST" action="{{ route('vendor-site.contact.submit', $vendor->slug) }}" id="enquiryForm" novalidate style="padding:24px 28px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px">
                    @csrf
                    <div class="vs-alert vs-alert-error" id="formAlert" role="alert" style="grid-column:1/-1;{{ $errors->any() ? '' : 'display:none' }}">Please correct the highlighted fields.</div>
                    {{-- Anti-spam: hidden from people, filled by bots. --}}
                    <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    @php
                        $err = fn($f) => $errors->has($f) ? 'vs-invalid' : '';
                        $inv = fn($f) => $errors->has($f) ? 'true' : 'false';
                    @endphp

                    <div class="vs-field">
                        <label class="vs-label" for="f-name">Full Name <span style="color:#B91C1C">*</span></label>
                        <input id="f-name" name="name" type="text" class="vs-input {{ $err('name') }}" value="{{ old('name') }}" autocomplete="name"
                            required minlength="3" maxlength="60" data-rule="name" aria-invalid="{{ $inv('name') }}" aria-describedby="e-name" placeholder="e.g. Priya Shah">
                        <div class="vs-err" id="e-name" role="alert">@error('name'){{ $message }}@enderror</div>
                    </div>

                    <div class="vs-field">
                        <label class="vs-label" for="f-mobile">Mobile Number <span style="color:#B91C1C">*</span></label>
                        <div class="vs-input-group {{ $err('mobile') }}">
                            <span class="vs-prefix" aria-hidden="true">+91</span>
                            <input id="f-mobile" name="mobile" type="tel" class="vs-input" value="{{ old('mobile') }}" autocomplete="tel-national"
                                required inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}" data-rule="mobile"
                                aria-invalid="{{ $inv('mobile') }}" aria-describedby="e-mobile" placeholder="10-digit mobile number">
                        </div>
                        <div class="vs-err" id="e-mobile" role="alert">@error('mobile'){{ $message }}@enderror</div>
                    </div>

                    <div class="vs-field">
                        <label class="vs-label" for="f-email">Email ID <span style="color:#B91C1C">*</span></label>
                        <input id="f-email" name="email" type="email" class="vs-input {{ $err('email') }}" value="{{ old('email') }}" autocomplete="email"
                            required maxlength="100" data-rule="email" aria-invalid="{{ $inv('email') }}" aria-describedby="e-email" placeholder="name@company.com">
                        <div class="vs-err" id="e-email" role="alert">@error('email'){{ $message }}@enderror</div>
                    </div>

                    <div class="vs-field">
                        <label class="vs-label" for="f-company">Company / Brand</label>
                        <input id="f-company" name="company" type="text" class="vs-input {{ $err('company') }}" value="{{ old('company') }}" autocomplete="organization"
                            maxlength="100" data-rule="company" aria-invalid="{{ $inv('company') }}" aria-describedby="e-company" placeholder="Optional">
                        <div class="vs-err" id="e-company" role="alert">@error('company'){{ $message }}@enderror</div>
                    </div>

                    <div class="vs-field">
                        <label class="vs-label" for="f-city">City</label>
                        <input id="f-city" name="city" type="text" class="vs-input {{ $err('city') }}" value="{{ old('city') }}" autocomplete="address-level2"
                            maxlength="50" data-rule="city" aria-invalid="{{ $inv('city') }}" aria-describedby="e-city" placeholder="Optional">
                        <div class="vs-err" id="e-city" role="alert">@error('city'){{ $message }}@enderror</div>
                    </div>

                    <div class="vs-field">
                        <label class="vs-label" for="f-media">Interested in</label>
                        <select id="f-media" name="media_id" class="vs-input">
                            <option value="">General enquiry</option>
                            @foreach ($mediaOptions as $m)
                                <option value="{{ $m->id }}" @selected((int) old('media_id', $selectedMedia) === $m->id)>
                                    {{ $m->hoarding_code ?: $m->media_code }} · {{ $m->media_title }}{{ $m->area_name ? ', ' . $m->area_name : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="vs-field" style="grid-column:1/-1">
                        <label class="vs-label" for="f-message">Message <span style="color:#B91C1C">*</span></label>
                        <textarea id="f-message" name="message" class="vs-input {{ $err('message') }}" rows="5" required minlength="10" maxlength="1000" data-rule="message"
                            aria-invalid="{{ $inv('message') }}" aria-describedby="e-message"
                            style="padding:12px;min-height:120px"
                            placeholder="e.g. Looking for 2 hoardings near FC Road for 1 month from 1 Jan">{{ old('message', $draft) }}</textarea>
                        <div style="display:flex;justify-content:space-between;gap:12px">
                            <div class="vs-err" id="e-message" role="alert">@error('message'){{ $message }}@enderror</div>
                            <small style="color:#475569;white-space:nowrap;margin-top:6px"><span id="msgCount">0</span> / 1000</small>
                        </div>
                    </div>

                    <div style="grid-column:1/-1;display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between">
                        <span style="font-size:12px;color:#475569">Your details are shared only with {{ $vendor->display_name }}.</span>
                        <button type="submit" class="vs-btn vs-btn-o" style="min-width:200px"><i class="fa-regular fa-paper-plane"></i>Send Enquiry</button>
                    </div>
                </form>
            @endif
        </section>

        <div style="margin-top:28px;display:flex;flex-wrap:wrap;gap:12px;justify-content:center">
            <a href="{{ route('vendor-site.show', $vendor->slug) }}#inventory" class="vs-btn vs-btn-n"><i class="fa-solid fa-table-cells-large"></i>Browse our inventory</a>
            <a href="{{ route('vendor-site.map', $vendor->slug) }}" class="vs-btn vs-btn-g"><i class="fa-regular fa-map"></i>See sites on the map</a>
        </div>
    </main>
@endsection

@section('scripts')
    @if (session('enquiry_sent') || $errors->any() || request('media'))
        <script>document.getElementById('enquiry').scrollIntoView();</script>
    @endif
    <script>
        // Enquiry form: the same rules as VendorSiteController::submitEnquiry,
        // checked as the visitor goes. The server checks again on submit.
        (function () {
            const form = document.getElementById('enquiryForm');
            if (!form) return;

            const letters = /^\p{L}[\p{L} .'-]*$/u;
            const rules = {
                name(v) {
                    if (!v) return 'Please enter your full name.';
                    if (v.length < 3) return 'Name must be at least 3 characters.';
                    if (v.length > 60) return 'Name cannot be more than 60 characters.';
                    if (!letters.test(v)) return 'Name can contain only letters, spaces, dots, hyphens and apostrophes.';
                },
                mobile(v) {
                    if (!v) return 'Please enter your mobile number.';
                    if (!/^\d+$/.test(v)) return 'Mobile number can contain digits only.';
                    if (v.length !== 10) return 'Mobile number must be exactly 10 digits (' + v.length + ' entered).';
                    if (!/^[6-9]/.test(v)) return 'Mobile number must start with 6, 7, 8 or 9.';
                    if (/^(\d)\1{9}$/.test(v)) return 'Please enter a real mobile number.';
                },
                email(v) {
                    if (!v) return 'Please enter your email ID.';
                    if (v.length > 100) return 'Email ID cannot be more than 100 characters.';
                    if (!/^[^@\s]+@[^@\s]+\.[a-z]{2,}$/i.test(v) || /\.\./.test(v)) return 'Please enter a valid email ID, e.g. name@company.com.';
                },
                company(v) {
                    if (!v) return;
                    if (v.length < 2) return 'Company / brand must be at least 2 characters.';
                    if (!/^[\p{L}\p{N}][\p{L}\p{N} .,&'()\/-]*$/u.test(v)) return 'Company / brand contains characters that are not allowed.';
                },
                city(v) {
                    if (!v) return;
                    if (v.length < 2) return 'City must be at least 2 characters.';
                    if (!letters.test(v)) return 'City can contain only letters and spaces.';
                },
                message(v) {
                    if (!v) return 'Please write your message.';
                    if (v.length < 10) return 'Please tell us a little more (at least 10 characters).';
                    if (v.length > 1000) return 'Message cannot be more than 1000 characters.';
                    if (!/\p{L}{2,}/u.test(v)) return 'Please write your message in words.';
                },
            };

            const fields = Array.from(form.querySelectorAll('[data-rule]'));
            const box = el => el.closest('.vs-input-group') || el;
            const touched = new Set();

            function check(el, show) {
                const value = el.value.replace(/\s+/g, el.tagName === 'TEXTAREA' ? '$&' : ' ').trim();
                const error = rules[el.dataset.rule](value);
                if (show) {
                    document.getElementById('e-' + el.name).textContent = error || '';
                    box(el).classList.toggle('vs-invalid', !!error);
                    box(el).classList.toggle('vs-valid', !error && value !== '');
                    el.setAttribute('aria-invalid', error ? 'true' : 'false');
                }
                return !error;
            }

            // Mobile: digits only, at most 10. A pasted "+91 98765-43210" becomes 9876543210.
            const mobile = document.getElementById('f-mobile');
            mobile.addEventListener('input', function () {
                // "+91…" is the country code (already shown as the prefix), never part of the number.
                let d = this.value.replace(/^\s*\+\s*91[\s-]*/, '').replace(/\D/g, '');
                if (d.length > 10 && d.startsWith('91')) d = d.slice(2);
                if (d.length > 10 && d.startsWith('0')) d = d.slice(1);
                this.value = d.slice(0, 10);
            });

            // Name / city: block digits as they are typed.
            ['f-name', 'f-city'].forEach(function (id) {
                document.getElementById(id).addEventListener('input', function () {
                    const cleaned = this.value.replace(/[0-9]/g, '');
                    if (cleaned !== this.value) this.value = cleaned;
                });
            });

            fields.forEach(function (el) {
                el.addEventListener('blur', function () { touched.add(el.name); check(el, true); });
                el.addEventListener('input', function () { if (touched.has(el.name)) check(el, true); });
            });

            // Message counter.
            const msg = document.getElementById('f-message'), count = document.getElementById('msgCount');
            const updateCount = () => { count.textContent = msg.value.length; count.style.color = msg.value.length > 1000 ? '#B91C1C' : ''; };
            msg.addEventListener('input', updateCount); updateCount();

            form.addEventListener('submit', function (e) {
                let firstBad = null;
                fields.forEach(function (el) {
                    touched.add(el.name);
                    if (!check(el, true) && !firstBad) firstBad = el;
                });
                document.getElementById('formAlert').style.display = firstBad ? '' : 'none';
                if (firstBad) {
                    e.preventDefault();
                    firstBad.focus();
                    return;
                }
                const btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Sending…';
            });
        })();
    </script>
@endsection
