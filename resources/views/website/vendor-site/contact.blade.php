@extends('website.vendor-site.layout')
@section('title', 'Contact · ' . $vendor->vendor_name)
@section('tagline', $totals['media'] . ' outdoor media sites')

@section('nav')
    @include('website.vendor-site.partials.nav', ['active' => 'contact'])
@endsection

@section('styles')
    <style>
        .vs-page-hero { background: #0F172A; color: #fff; padding: 56px 0 96px; }
        .vs-contact { border: 1px solid #E5E7EB; border-radius: 16px; overflow: hidden; background: #fff; box-shadow: 0 12px 32px rgba(15, 23, 42, .08); }
        .vs-contact-brand { padding: 24px 28px; display: flex; flex-wrap: wrap; align-items: center; gap: 18px; border-bottom: 1px solid #F1F5F9; }
        .vs-contact-actions { margin-left: auto; display: flex; flex-wrap: wrap; gap: 10px; }
        .vs-contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .vs-contact-item { display: flex; gap: 14px; align-items: flex-start; padding: 26px 28px; }
        .vs-contact-item + .vs-contact-item { border-left: 1px solid #F1F5F9; }
        .vs-contact-icon { width: 46px; height: 46px; flex: none; border-radius: 12px; background: #FFF7ED; color: #C2410C; display: grid; place-items: center; font-size: 18px; }
        .vs-contact-label { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .06em; }
        .vs-contact-value { display: block; font-size: 16px; font-weight: 700; margin-top: 4px; line-height: 1.5; text-decoration: none; }
        @media (max-width: 640px) {
            .vs-page-hero { padding: 36px 0 80px; }
            .vs-contact-actions { margin-left: 0; }
            .vs-contact-item + .vs-contact-item { border-left: 0; border-top: 1px solid #F1F5F9; }
        }
    </style>
@endsection

@section('content')
    <section class="vs-page-hero">
        <div class="vs-wrap">
            <div style="font-size:13px;color:#CBD5E1"><a href="{{ route('vendor-site.show', $vendor->slug) }}" style="color:#FDBA74;font-weight:700">Home</a> / Contact</div>
            <h1 style="margin:10px 0 8px;font-size:40px;font-weight:800">Contact {{ $vendor->vendor_name }}</h1>
            <p style="margin:0;font-size:16px;color:#CBD5E1;max-width:560px">Ask about any of our {{ $totals['media'] }} sites, their availability or rates. We usually reply the same day.</p>
        </div>
    </section>

    <main class="vs-wrap" style="margin-top:-56px;padding-bottom:64px">
        <div class="vs-contact">
            <div class="vs-contact-brand">
                <span class="vs-mono" style="width:64px;height:64px;font-size:22px">
                    @if ($vendor->logo_url)<img src="{{ $vendor->logo_url }}" alt="">@else{{ $vendor->initials }}@endif
                </span>
                <div>
                    <div style="font-size:22px;font-weight:800">{{ $vendor->vendor_name }}</div>
                    <div style="color:#475569;font-size:14px;margin-top:4px">Outdoor media · {{ $totals['media'] }} sites across {{ $totals['districts'] }} district{{ $totals['districts'] == 1 ? '' : 's' }}</div>
                </div>
                <div class="vs-contact-actions">
                    @if ($contact['tel'])
                        <a href="tel:{{ $contact['tel'] }}" class="vs-btn vs-btn-o"><i class="fa-solid fa-phone"></i>Call</a>
                    @endif
                    @if ($contact['whatsapp'])
                        <a href="https://wa.me/{{ $contact['whatsapp'] }}?text={{ rawurlencode('Hi ' . $vendor->vendor_name . ', I saw your media on Brand Adda and would like to know more.') }}"
                            target="_blank" rel="noopener" class="vs-btn" style="background:#16A34A;color:#fff"><i class="fa-brands fa-whatsapp"></i>WhatsApp</a>
                    @endif
                    @if ($contact['email'])
                        <a href="mailto:{{ $contact['email'] }}?subject={{ rawurlencode('Media enquiry via Brand Adda') }}" class="vs-btn vs-btn-g"><i class="fa-regular fa-envelope"></i>Email</a>
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
                <p style="margin:6px 0 0;color:#475569;font-size:14px">Tell us what you need and {{ $vendor->vendor_name }} will get back to you.</p>
            </div>

            @if (session('enquiry_sent'))
                <div style="padding:40px 28px;text-align:center">
                    <div style="width:64px;height:64px;margin:0 auto 14px;border-radius:999px;background:#DCFCE7;color:#166534;display:grid;place-items:center;font-size:28px"><i class="fa-solid fa-check"></i></div>
                    <h3 style="margin:0;font-size:20px;font-weight:800">Thank you! Your enquiry has been sent.</h3>
                    <p style="margin:8px 0 18px;color:#475569">{{ $vendor->vendor_name }} will contact you soon on the phone or email you gave.</p>
                    <a href="{{ route('vendor-site.contact', $vendor->slug) }}#enquiry" class="vs-btn vs-btn-g">Send another enquiry</a>
                </div>
            @else
                <form method="POST" action="{{ route('vendor-site.contact.submit', $vendor->slug) }}" novalidate style="padding:24px 28px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px">
                    @csrf
                    @if ($errors->any())
                        <div class="vs-alert vs-alert-error" role="alert" style="grid-column:1/-1">Please correct the highlighted fields.</div>
                    @endif
                    {{-- Anti-spam: hidden from people, filled by bots. --}}
                    <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    @foreach ([
                        ['name', 'Full Name', 'text', true, 'name'],
                        ['mobile', 'Mobile Number', 'tel', true, 'tel'],
                        ['email', 'Email ID', 'email', true, 'email'],
                        ['company', 'Company / Brand', 'text', false, 'organization'],
                        ['city', 'City', 'text', false, 'address-level2'],
                    ] as [$field, $label, $type, $required, $auto])
                        <div>
                            <label class="vs-label" for="f-{{ $field }}">{{ $label }} @if ($required)<span style="color:#B91C1C">*</span>@endif</label>
                            <input id="f-{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="vs-input" value="{{ old($field) }}" autocomplete="{{ $auto }}"
                                @if ($required) required @endif @if ($field === 'mobile') inputmode="numeric" maxlength="14" @endif
                                style="{{ $errors->has($field) ? 'border-color:#DC2626' : '' }}" aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}">
                            @error($field)<div style="color:#B91C1C;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
                        </div>
                    @endforeach

                    <div>
                        <label class="vs-label" for="f-media">Interested in</label>
                        <select id="f-media" name="media_id" class="vs-input">
                            <option value="">General enquiry</option>
                            @foreach ($mediaOptions as $m)
                                <option value="{{ $m->id }}" @selected((int) old('media_id', $selectedMedia) === $m->id)>
                                    {{ $m->hoarding_code ?: $m->media_code }} · {{ $m->media_title }}{{ $m->area_name ? ', ' . $m->area_name : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="grid-column:1/-1">
                        <label class="vs-label" for="f-message">Message <span style="color:#B91C1C">*</span></label>
                        <textarea id="f-message" name="message" class="vs-input" rows="5" required maxlength="2000"
                            style="padding:12px;min-height:120px;{{ $errors->has('message') ? 'border-color:#DC2626' : '' }}"
                            placeholder="e.g. Looking for 2 hoardings near FC Road for 1 month from 1 Jan">{{ old('message') }}</textarea>
                        @error('message')<div style="color:#B91C1C;font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
                    </div>

                    <div style="grid-column:1/-1;display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between">
                        <span style="font-size:12px;color:#475569">Your details are shared only with {{ $vendor->vendor_name }}.</span>
                        <button type="submit" class="vs-btn vs-btn-o" style="min-width:200px"><i class="fa-regular fa-paper-plane"></i>Send Enquiry</button>
                    </div>
                </form>
            @endif
        </section>

        <div style="margin-top:28px;display:flex;flex-wrap:wrap;gap:12px;justify-content:center">
            <a href="{{ route('vendor-site.show', $vendor->slug) }}#inventory" class="vs-btn vs-btn-n"><i class="fa-solid fa-table-cells-large"></i>Browse our inventory</a>
            <a href="{{ route('vendor-site.show', $vendor->slug) }}#map" class="vs-btn vs-btn-g"><i class="fa-regular fa-map"></i>See sites on the map</a>
        </div>
    </main>
@endsection

@section('scripts')
    @if (session('enquiry_sent') || $errors->any() || request('media'))
        <script>document.getElementById('enquiry').scrollIntoView();</script>
    @endif
@endsection
