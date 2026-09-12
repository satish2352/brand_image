<style>
    .media-card-hording {
        background: #FFFFFF;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }

    /* IMAGE TOP */
    .media-img {
        height: 250px;
        /* FIXED HEIGHT */
        width: 100%;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    /* TEXT AREA */
    .media-content {
        padding: 15px;
    }

    .carousel-control-prev,
    .carousel-control-next {
        top: 50%;
        bottom: unset;
        transform: translateY(-50%);
        height: 500px;
        align-items: center;
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        filter: invert(1) opacity(1);
        background: none !important;
    }

    /* CARD FULL HEIGHT FIX */
    .single-latest-news,
    .media-card-hording {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    /* IMAGE FIXED HEIGHT */
    .latest-news-bg,
    .media-img {
        height: 220px;
        background-size: cover;
        background-position: center;
        flex-shrink: 0;
    }

    /* CONTENT AREA FLEX */
    .news-text-box,
    .media-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    /* PUSH BUTTONS TO BOTTOM */
    .card-actions {
        margin-top: auto;
    }

    @media (max-width: 768px) {

        .latest-news-bg,
        .media-img {
            height: 180px;
        }
    }
</style>
{{-- ================= HERO =================
     The admin-managed slider is the full-bleed background; the headline,
     copy and calls to action sit over it. --}}
<section class="bi-hero">

    <div class="bi-hero-bg">
        @if ($sliders->count())

            <div id="carouselExampleControls" class="carousel slide" data-bs-ride="carousel" data-bs-interval="6000">

                <div class="carousel-inner">

                    @foreach ($sliders as $key => $slider)
                        <div class="carousel-item {{ $key === 0 ? 'active' : '' }}">

                            <img src="{{ config('fileConstants.IMAGE_VIEW') . $slider->desktop_image }}"
                                class="d-block w-100 carousel-img"
                                loading="{{ $key === 0 ? 'eager' : 'lazy' }}" decoding="async"
                                data-desktop="{{ config('fileConstants.IMAGE_VIEW') . $slider->desktop_image }}"
                                data-mobile="{{ config('fileConstants.IMAGE_VIEW') . $slider->mobile_image }}"
                                alt="Home Slider {{ $key + 1 }}">

                        </div>
                    @endforeach

                </div>

                {{-- CONTROLS --}}
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleControls"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>

                <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleControls"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>

            </div>
        @else
            {{-- No slider uploaded yet — the hero still reads correctly on a
                 plain brand-dark background instead of collapsing. --}}
            <div class="bi-hero-fallback"></div>
        @endif
    </div>

    <div class="bi-hero-inner">
        <div class="container">

            <h1 class="bi-hero-title" data-aos="fade-up">
                <span class="bi-hero-line1">Maharashtra's Outdoor </span>
                <span class="bi-hero-line2">Media Paltform</span>
            </h1>

            <p class="bi-hero-sub" data-aos="fade-up" data-aos-delay="100">
              Brand Adda is a technology-driven OOH media platform that 
              fragmented outdoor advertising inventory onto one searchable ecosystem
            </p>

            <div class="bi-hero-actions" data-aos="fade-up" data-aos-delay="200">
                <a href="{{ route('website.explore') }}" class="bi-hero-btn bi-hero-btn-solid">
                    <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                    <span>Explore the Map</span>
                </a>
                <a href="#mediaSearch" class="bi-hero-btn bi-hero-btn-ghost">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <span>Search Media</span>
                </a>
            </div>

            {{-- The real map page, framed under the buttons the way the reference
                 floats its product shot. ?embed=1 drops the site header inside
                 the frame.

                 This is a whole second page — its own Laravel render, its own
                 copy of the stylesheets, Leaflet, and a marker for every active
                 hoarding — so when it loads matters as much as what it loads.
                 loading="lazy" was not enough: the panel sits roughly one screen
                 down, well inside the distance at which the browser decides a
                 lazy frame is "about to be needed" and fetches it anyway, so it
                 was competing with the hero image for bandwidth on every visit.

                 Holding the URL in data-src instead means nothing is requested
                 until the panel is genuinely scrolled to, and then only once. --}}
            <div class="bi-hero-panel" data-aos="fade-up" data-aos-delay="300">
                <iframe class="bi-hero-frame" data-src="{{ route('website.explore') }}?embed=1"
                    title="Explore available outdoor media on the map" loading="lazy"></iframe>
            </div>

        </div>
    </div>
</section>

{{-- Loads the framed map page the moment its panel actually comes into view.
     Deliberately not deferred to a separate file: it has to be able to start
     the load before the rest of the page's JavaScript has finished arriving. --}}
<script>
    (function () {
        var frame = document.querySelector('.bi-hero-frame[data-src]');
        if (!frame) return;

        function load() {
            var src = frame.getAttribute('data-src');
            if (!src) return;
            frame.removeAttribute('data-src');
            frame.src = src;
        }

        // No IntersectionObserver (old browsers) — fall back to loading it once
        // the rest of the page has finished, which is still later than the
        // browser's own lazy heuristic was firing.
        if (!('IntersectionObserver' in window)) {
            window.addEventListener('load', load);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                observer.disconnect();
                load();
            });
        }, {
            // A small margin so the map has a moment to draw before it is
            // properly on screen, without pre-loading it from the top of the page.
            rootMargin: '200px 0px'
        });

        observer.observe(frame);
    })();
</script>

<!-- FEATURES SECTION -->
{{-- Lifted so it overlaps the bottom of the hero, the way the reference floats
     its panel over the artwork. --}}
<div class="list-section bi-float-panel pt-80 pb-80">
    <div class="container">

        <div class="row d-flex justify-content-center">

            <!-- Feature 1 -->
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0 p-2 p-lg-0">
                <div class="list-box d-flex align-items-center">
                    <div class="list-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="content">
                        <h3>Wide Media Reach</h3>
                        <p>Extensive outdoor media coverage <br>across prime locations in Maharashtra.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0 p-2 p-lg-0">
                <div class="list-box d-flex align-items-center">
                    <div class="list-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="content">
                        <h3>Creative Strategy</h3>
                        <p>Innovative campaign planning <br>tailored to your brand objectives.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0 p-2 p-lg-0">
                <div class="list-box d-flex align-items-center">
                    <div class="list-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="content">
                        <h3>Proven Results</h3>
                        <p>Data-driven execution ensuring <br>maximum visibility and ROI.</p>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
<!-- END FEATURES SECTION -->

<!-- Search Bar Section -->
{{-- id is the target of the hero's "Search Media" button; it lives here rather
     than in the shared partial, which the /search page also renders. --}}
<div id="mediaSearch">
    @include('website.search-form')
</div>
<!-- end Bar Section -->
<!-- SERVICES SECTION -->
<section class="services-section">
    <div class="container">
        <div class="services-header text-center">
            <span class="services-subtitle">OUR SERVICES</span>
            <h2 class="services-title">
                Outdoor Media <span>Solutions</span>
            </h2>
            <p class="services-desc">
                Comprehensive outdoor advertising solutions tailored to reach your target
                audience across India's diverse landscape.
            </p>
        </div>


        @php
            // One definition per card, in display order. Icons stay on the
            // bootstrap-icons set the layout already loads.
            $services = [
                [
                    'title' => 'Traditional OOH',
                    'desc' => 'Billboards, hoardings, and unipoles across prime locations.',
                    'icon' => 'bi-display',
                    'image' => 'traditional_ooh.webp',
                ],
                [
                    'title' => 'Digital Displays',
                    'desc' => 'LED screens and digital billboards with dynamic content.',
                    'icon' => 'bi-layers',
                    'image' => 'digital_displays.webp',
                ],
                [
                    'title' => 'Wall Painting',
                    'desc' => 'Large-scale wall murals and paintings for lasting impressions.',
                    'icon' => 'bi-brush',
                    'image' => 'wall_painting.webp',
                ],
                [
                    'title' => 'Mall Media',
                    'desc' => 'Strategic placements in shopping malls to reach consumers.',
                    'icon' => 'bi-shop',
                    'image' => 'mall_media.webp',
                ],
                [
                    'title' => 'Office Branding',
                    'desc' => 'Corporate signage and office branding solutions.',
                    'icon' => 'bi-building',
                    'image' => 'office_branding.webp',
                ],
                [
                    'title' => 'Transit Media',
                    'desc' => 'Bus, metro, and auto advertising for mobile exposure.',
                    'icon' => 'bi-train-front',
                    'image' => 'transit_media.webp',
                ],
                [
                    'title' => 'Airport Branding',
                    'desc' => 'Premium airport advertising targeting affluent travelers.',
                    'icon' => 'bi-airplane',
                    'image' => 'airport_branding.webp',
                ],
                [
                    'title' => 'Wall Wraps',
                    'desc' => 'Full building wraps and facade branding for landmarks.',
                    'icon' => 'bi-palette',
                    'image' => 'wall_wraps.webp',
                ],
            ];
        @endphp

        <div class="row g-4">

            @foreach ($services as $service)
                <div class="col-lg-3 col-md-6">
                    <div class="service-card">

                        {{-- Decorative: the copy beside it already names the service,
                             so the photo is hidden from screen readers and its alt
                             is left empty. --}}
                        <div class="service-media" aria-hidden="true">
                            <img src="{{ asset('assets/img/' . $service['image']) }}" alt=""
                                loading="lazy" decoding="async" width="1536" height="1024">
                        </div>

                        <div class="service-icon"><i class="bi {{ $service['icon'] }}"></i></div>
                        {{-- Escaped first, then the FIRST space becomes a break, so every
                             title reads as two lines regardless of how wide it would fit. --}}
                        <h4>{!! preg_replace('/ /', ' <br>', e($service['title']), 1) !!}</h4>
                        <!-- <p>{{ $service['desc'] }}</p> -->
                    </div>
                </div>
            @endforeach

        </div>
    </div>
</section>

<div class="latest-news pt-150">
    <div class="container">

        {{-- Title --}}
        <div class="row">
            <div class="col-lg-8 offset-lg-2 text-center">
                <div class="section-title hording-section-title">
                    <span class="bi-eyebrow">Premium Inventory</span>
                    {{-- the span was left unclosed here, which handed the rest of the
                         document to it until the browser repaired it --}}
                    <h3 class="bi-section-title">Hoardings / <span class="accent">Billboards</span></h3>
                    <p>Turn busy roads into powerful brand touchpoints with premium hoardings and billboard solutions.
                    </p>
                </div>
            </div>
        </div>
        {{-- ================= HOARDINGS SECTION ================= --}}
        {{-- <div class="row mb-4"> --}}

        <div class="hoarding-slider-wrapper position-relative">

            <div class="swiper hoarding-slider mb-4">
                <div class="swiper-wrapper">


                    @foreach ($billboards as $media)
                        @if ((int) $media->category_id === 1)

                            {{-- <div class="col-lg-4 col-md-6 mb-5"> --}}
                            <div class="swiper-slide">
                                <div class="single-latest-news">

                                    {{-- <div class="latest-news-bg"
                                    style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')"  class="card-img-fit">
                        </div> --}}
                                    @php
                                        $isBillboard = (int) $media->category_id === 1;
                                        $isBooked = (int) ($media->is_booked ?? 0);
                                        $width = (float) ($media->width ?? 0);
                                        $height = (float) ($media->height ?? 0);
                                        $sqft = $width * $height;
                                    @endphp
                                    <div class="latest-news-bg card-img-fit">

                                        <img src="{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}"
                                            loading="lazy" decoding="async"
                                            alt="{{ $media->area_name ?? $media->category_name }}"
                                            style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">

                                        @if ($isBooked === 1)
                                            <span class="media-badge booked">Booked</span>
                                        @else
                                            <span class="media-badge available">Available</span>
                                        @endif

                                    </div>

                                    <div class="news-text-box">

                                        {{-- <h3>{{ $media->media_title ?? $media->category_name }}</h3> --}}
                                        <h3 style="font-size: 21px;">
                                            <a href="{{ route('website.media-details', base64_encode($media->id)) }}">
                                                {{-- Same pairing the search result cards use: the media's own
                                                     title, falling back to its category, then the area. --}}
                                                {{ \Illuminate\Support\Str::limit(ucfirst($media->media_title ?? $media->category_name) . ' ' . $media->area_name, 25, '...') }}
                                            </a>
                                        </h3>


                                        <p class="blog-meta">
                                            <i class="fas fa-map-marker-alt"></i>
                                            {{ $media->area_name }}, {{ $media->city_name }}
                                        </p>

                                        <div class="col-12 mb-2">
                                            <strong>Size:</strong>
                                            {{ number_format($media->width, 2) }} x
                                            {{ number_format($media->height, 2) }} ft

                                        </div>
                                        <div class="col-12 mb-2">
                                            <strong>Total Area:</strong>
                                            {{ number_format($sqft, 2) }} SQFT
                                        </div>

                                        <div class="media-price">
                                            ₹ {{ number_format($media->price, 2) }}
                                            <small class="pricepermonth">/Month</small>
                                        </div>

                                        {{-- href="https://www.google.com/maps/search/?api=1&query={{ urlencode($media->area_name . ', ' . $media->city_name) }}" --}}
                                        <div
                                            class="media-map mt-4 d-flex align-items-center justify-content-between gap-3">
                                            {{-- <a href="https://www.google.com/maps"> --}}
                                            {{-- </a> --}}

                                            <a href="https://www.google.com/maps?q={{ $media->latitude }},{{ $media->longitude }}"
                                                target="_blank"
                                                class="text-muted d-inline-flex align-items-center gap-1">
                                                <img src="{{ asset('assets/img/105.png') }}" width="30">
                                                <span>View on Map</span>
                                            </a>
                                            @if (!empty($media->panorama_image))
                                                <a href="{{ url('./panorama.html?img=' . urlencode(config('fileConstants.IMAGE_VIEW') . $media->panorama_image)) }}"
                                                    target="_blank"
                                                    class="text-muted d-inline-flex align-items-center gap-1">

                                                    <img src="{{ asset('assets/img/360view.png') }}" width="30">
                                                    <span>360° View</span>

                                                </a>
                                            @endif
                                        </div>
                                        @php
                                            $isBillboard = (int) $media->category_id === 1;
                                            $isBooked = (int) ($media->is_booked ?? 0);
                                        @endphp

                                        <div class="card-actions">

                                            {{-- ================= BILLBOARDS ================= --}}
                                            @if ($isBillboard)
                                                @if ($isBooked === 0)
                                                    {{-- READ MORE --}}
                                                    <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
                                                        class="card-btn read">
                                                        Read More →
                                                    </a>

                                                    {{-- ADD TO CART --}}
                                                    @auth('website')
                                                        <a href="{{ route('cart.add', base64_encode($media->id)) }}"
                                                            class="btn card-btn cart">
                                                            Add to Cart
                                                        </a>
                                                    @else
                                                        <button class="btn card-btn cart" data-bs-toggle="modal"
                                                            data-bs-target="#authModal"
                                                            onclick="setRedirect('{{ route('cart.add', base64_encode($media->id)) }}')">
                                                            Add to Cart
                                                        </button>
                                                    @endauth
                                                @else
                                                    {{-- BOOKED → ONLY READ MORE --}}
                                                    <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
                                                        class="card-btn read">
                                                        Read More →
                                                    </a>
                                                @endif

                                                {{-- ================= OTHER MEDIA ================= --}}
                                            @else
                                                <a href="{{ route('contact.create') }}#contact-form"
                                                    class="card-btn contact">
                                                    Contact Us
                                                </a>
                                            @endif

                                        </div>

                                    </div>
                                </div>
                            </div>

                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Slider controls: prev/next on the right, sitting under the
                 cards. The autoplay toggle and the progress dots used to sit
                 on the left of this bar. --}}
            <div class="bi-slider-bar">

                <div class="bi-slider-group">
                    <button type="button" class="swiper-btn swiper-btn-prev" aria-label="Previous slide">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="swiper-btn swiper-btn-next" aria-label="Next slide">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<div class="latest-news pb-150 pt-150">
    <div class="container">

        {{-- Title --}}
        <div class="row">
            <div class="col-lg-8 offset-lg-2 text-center">
                <div class="section-title">
                    <span class="bi-eyebrow">Beyond Billboards</span>
                    <h3 class="bi-section-title">Other <span class="accent">Media</span></h3>
                    <p>Extend your brand reach with strategic non-traditional media that captures attention in everyday
                        environments.</p>
                </div>
            </div>
        </div>
        {{-- ================= OTHER MEDIA SECTION =================
             Billboards have their own slider above, so they are filtered out
             here first: the list can arrive non-empty and still leave this row
             with nothing in it, which @forelse alone would not catch. --}}
        @php
            $otherMediaList = collect($otherMedia)->reject(
                fn($m) => $m->category_name === 'Hoardings/Billboards',
            );
        @endphp
        <div class="row">

            @forelse ($otherMediaList as $media)
                @if ($media->category_name !== 'Hoardings/Billboards')
                    @php
                        $width = (float) ($media->width ?? 0);
                        $height = (float) ($media->height ?? 0);
                        $sqft = $width * $height;
                    @endphp
                    <div class="col-lg-4 col-md-6 mb-5">
                        <div class="media-card-hording">
                            <img class="media-img"
                                src="{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}"
                                loading="lazy" decoding="async"
                                alt="{{ $media->area_name ?? $media->category_name }}"
                                style="object-fit:cover;">
                            <div class="media-content">
                                <h3 style="font-size: 21px;">
                                    <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
                                        style="color: #0F172A">
                                        {{ $media->area_name ?? $media->category_name }} {{ $media->facing }}
                                    </a>
                                </h3>
                                <p class="blog-meta">
                                    <i class="fas fa-map-marker-alt"></i>
                                    {{ $media->area_name }}, {{ $media->city_name }}
                                </p>

                                <div class="col-12 mb-2 d-flex">
                                    <strong>Size : </strong>
                                    {{ number_format($media->width, 2) }} x {{ number_format($media->height, 2) }} ft

                                </div>
                                <div class="col-12 mb-2 d-flex">
                                    <strong>Area : </strong>
                                    {{ number_format($sqft, 2) }} SQFT
                                </div>
                                <div class="media-price">
                                    ₹ {{ number_format($media->price, 2) }}
                                </div>

                                <div class="media-map mt-4 d-flex align-items-center justify-content-between gap-3">
                                    @if (!empty($media->panorama_image))
                                        <a href="{{ $media->panorama_image }}" target="_blank"
                                            class="text-muted d-inline-flex align-items-center gap-1 mt-1">
                                            <img src="{{ asset('assets/img/360view.png') }}" style="width : 20px">
                                            <span>360° View</span>
                                        </a>
                                    @endif
                                </div>
                                @php
                                    $isBillboard = (int) $media->category_id === 1;
                                    $isBooked = (int) ($media->is_booked ?? 0);
                                @endphp

                                <div class="card-actions">

                                    {{-- ================= BILLBOARDS ================= --}}
                                    @if ($isBillboard)
                                        @if ($isBooked === 0)
                                            {{-- READ MORE --}}
                                            <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
                                                class="card-btn read">
                                                Read More →
                                            </a>

                                            {{-- ADD TO CART --}}
                                            @auth('website')
                                                <a href="{{ route('cart.add', base64_encode($media->id)) }}"
                                                    class="btn card-btn cart">
                                                    Add to Cart
                                                </a>
                                            @else
                                                <button class="btn card-btn cart" data-bs-toggle="modal"
                                                    data-bs-target="#authModal"
                                                    onclick="setRedirect('{{ route('cart.add', base64_encode($media->id)) }}')">
                                                    Add to Cart
                                                </button>
                                            @endauth
                                        @else
                                            {{-- BOOKED → ONLY READ MORE --}}
                                            <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
                                                class="card-btn read">
                                                Read More →
                                            </a>
                                        @endif

                                        {{-- ================= OTHER MEDIA ================= --}}
                                    @else
                                        <a href="{{ route('contact.create', ['media' => base64_encode($media->id)]) }}#contact-form"
                                            class="card-btn contact">
                                            Contact Us
                                        </a>

                                        <a href="{{ route('website.media-details', base64_encode($media->id)) }}"
                                            class="card-btn read">
                                            Read More →
                                        </a>
                                    @endif

                                </div>

                            </div>
                        </div>
                    </div>

                @endif
            @empty
                {{-- Nothing to show. The heading above promises a row of cards,
                     so an empty section reads as a page that failed to load
                     rather than as a catalogue that is still being filled. --}}
                <div class="col-12">
                    <div class="bi-media-empty">
                        <i class="bi bi-collection" aria-hidden="true"></i>
                        <h4>No other media listed yet</h4>
                        <p>
                            We are adding transit, mall, airport and wall media to the
                            platform. Tell us what you are looking for and our team will
                            source it for you.
                        </p>
                    </div>
                </div>
            @endforelse
        </div>

    </div>
</div>

<!-- PROCESS SECTION -->
<!-- JOURNEY SECTION -->
<section class="journey-section">
    <div class="container">

        <div class="journey-header" data-aos="fade-up">
            <span class="bi-eyebrow">How It Works</span>
            <h2 class="journey-title">Our <span class="accent">Long-Term</span> Journey</h2>
            <!-- <span class="journey-rule" aria-hidden="true"></span> -->
            <p class="journey-desc">
                From discovery to impact &mdash; a seamless journey for your OOH success.
            </p>
        </div>

        {{-- An ordered list: these are six numbered steps, and the arrows between
             them are decoration drawn by CSS rather than markup. --}}
        <ol class="journey-steps">

                <li class="journey-step" data-aos="fade-up" data-aos-delay="100">
                    {{-- The glowing dot that runs up the arrow into this step. Pure
                         decoration, and the first step has no arrow before it. --}}
                    <span class="journey-spark" aria-hidden="true"></span>
                    <div class="journey-icon">
                        {{-- The step number is drawn into the artwork, so it is not
                             repeated as markup — only as the label a screen reader
                             hears, which would otherwise lose the ordering. --}}
                        <img src="{{ asset('assets/img/journey/discover.webp') }}" alt="Step 01"
                            decoding="async" width="256" height="256">
                    </div>
                    <h4>Discover</h4>
                    <p>Explore the right OOH opportunities across locations.</p>
                </li>

                <li class="journey-step" data-aos="fade-up" data-aos-delay="200">
                    {{-- The glowing dot that runs up the arrow into this step. Pure
                         decoration, and the first step has no arrow before it. --}}
                    <span class="journey-spark" aria-hidden="true"></span>
                    <div class="journey-icon">
                        {{-- The step number is drawn into the artwork, so it is not
                             repeated as markup — only as the label a screen reader
                             hears, which would otherwise lose the ordering. --}}
                        <img src="{{ asset('assets/img/journey/plan.webp') }}" alt="Step 02"
                            decoding="async" width="256" height="256">
                    </div>
                    <h4>Plan</h4>
                    <p>Create targeted campaigns that fit your goals.</p>
                </li>

                <li class="journey-step" data-aos="fade-up" data-aos-delay="300">
                    {{-- The glowing dot that runs up the arrow into this step. Pure
                         decoration, and the first step has no arrow before it. --}}
                    <span class="journey-spark" aria-hidden="true"></span>
                    <div class="journey-icon">
                        {{-- The step number is drawn into the artwork, so it is not
                             repeated as markup — only as the label a screen reader
                             hears, which would otherwise lose the ordering. --}}
                        <img src="{{ asset('assets/img/journey/availability.webp') }}" alt="Step 03"
                            decoding="async" width="256" height="256">
                    </div>
                    <h4>Check Availability</h4>
                    <p>Get real-time inventory and availability.</p>
                </li>

                <li class="journey-step" data-aos="fade-up" data-aos-delay="400">
                    {{-- The glowing dot that runs up the arrow into this step. Pure
                         decoration, and the first step has no arrow before it. --}}
                    <span class="journey-spark" aria-hidden="true"></span>
                    <div class="journey-icon">
                        {{-- The step number is drawn into the artwork, so it is not
                             repeated as markup — only as the label a screen reader
                             hears, which would otherwise lose the ordering. --}}
                        <img src="{{ asset('assets/img/journey/book.webp') }}" alt="Step 04"
                            decoding="async" width="256" height="256">
                    </div>
                    <h4>Book</h4>
                    <p>Secure your preferred spaces with ease.</p>
                </li>

                <li class="journey-step" data-aos="fade-up" data-aos-delay="500">
                    {{-- The glowing dot that runs up the arrow into this step. Pure
                         decoration, and the first step has no arrow before it. --}}
                    <span class="journey-spark" aria-hidden="true"></span>
                    <div class="journey-icon">
                        {{-- The step number is drawn into the artwork, so it is not
                             repeated as markup — only as the label a screen reader
                             hears, which would otherwise lose the ordering. --}}
                        <img src="{{ asset('assets/img/journey/execute.webp') }}" alt="Step 05"
                            decoding="async" width="256" height="256">
                    </div>
                    <h4>Execute</h4>
                    <p>Bring your campaign to life with on-ground excellence.</p>
                </li>

                <li class="journey-step" data-aos="fade-up" data-aos-delay="600">
                    {{-- The glowing dot that runs up the arrow into this step. Pure
                         decoration, and the first step has no arrow before it. --}}
                    <span class="journey-spark" aria-hidden="true"></span>
                    <div class="journey-icon">
                        {{-- The step number is drawn into the artwork, so it is not
                             repeated as markup — only as the label a screen reader
                             hears, which would otherwise lose the ordering. --}}
                        <img src="{{ asset('assets/img/journey/report.webp') }}" alt="Step 06"
                            decoding="async" width="256" height="256">
                    </div>
                    <h4>Report</h4>
                    <p>Measure performance and see real impact.</p>
                </li>

        </ol>
    </div>
</section>

<section class="about-modern">
    <div class="container">
        <div class="row align-items-center">


            <div class="col-lg-6">
                <span class="about-subtitle">ABOUT US</span>

                <h2 class="about-title">
                    Powering the Future of 
                    <span>Outdoor Advertising</span>
                </h2>

                <p>
                   Brand Adda is a technology-driven OOH media platform that brings fragmented outdoor 
                   advertising inventory onto one searchable ecosystem, helping brands and agencies 
                   discover, plan and execute campaigns faster, more efficiently and with greater 
                   transparency.  </p>

                <p>Brand Adda is being built to create a connected OOH ecosystem where 
                   brands, agencies, media owners and local OOH partners can discover opportunities, 
                   plan campaigns and grow their business through one technology-driven platform. Our 
                   objective is to make outdoor media more accessible, efficiently planned and better 
                   utilized across urban, semi-urban and rural markets....</p>

                <a href="{{ route('website.about') }}" class="about-btn">Know More</a>
            </div>

            <div class="col-lg-5 ms-lg-5">
                <div class="stats-grid">

                    <div class="stat-card stat-card-years">
                        <span class="visually-hidden">12+ Years Experience</span>
                    </div>

                    <div class="stat-card stat-card-clients">
                        <span class="visually-hidden">300+ Happy Clients</span>
                    </div>

                    <div class="stat-card stat-card-cities">
                        <span class="visually-hidden">50+ Cities Covered</span>
                    </div>

                    <div class="stat-card stat-card-campaigns">
                        <span class="visually-hidden">500+ Campaigns</span>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<!-- CTA SECTION -->
<section class="cta-section">
    <div class="container">
        <div class="cta-box text-center">

            <span class="bi-eyebrow">Let's Talk</span>

            <h2>
                Ready to Amplify Your <br>
                <span>Brand Presence?</span>
            </h2>

            <p>
                Let's discuss how we can transform your advertising vision
                into city-wide visibility. Get a free consultation today.
            </p>

            <div class="cta-actions">
                <a href="{{ url('/contact-us') }}" class="btn-cta primary" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700"
                    data-aos-easing="ease-out-cubic">
                    Get Free Quote <i class="bi bi-arrow-right"></i>
                </a>

                <a href="tel:+917770018173" class="btn-cta outline" data-aos="fade-up" data-aos-delay="220" data-aos-duration="700"
                    data-aos-easing="ease-out-cubic">
                    <i class="bi bi-telephone"></i> Call Us Now
                </a>
            </div>

        </div>
    </div>
</section>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const hoardingSwiper = new Swiper(".hoarding-slider", {
            slidesPerView: 3,
            spaceBetween: 20,
            loop: true,
            centeredSlides: false,

            navigation: {
                nextEl: ".swiper-btn-next",
                prevEl: ".swiper-btn-prev",
            },

            pagination: {
                el: ".hoarding-pagination",
                clickable: true,
            },

            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },

            breakpoints: {
                0: {
                    slidesPerView: 1
                },
                576: {
                    slidesPerView: 2
                },
                768: {
                    slidesPerView: 3
                },
                992: {
                    slidesPerView: 3
                },
                1200: {
                    slidesPerView: 3
                }
            }
        });

        // Autoplay toggle. Icon and aria-pressed track the real Swiper state
        // rather than a separate flag, so they cannot drift out of sync.
        const playBtn = document.getElementById('hoardingPlay');

        if (playBtn && hoardingSwiper.autoplay) {
            playBtn.addEventListener('click', function() {
                const running = hoardingSwiper.autoplay.running;

                if (running) {
                    hoardingSwiper.autoplay.stop();
                } else {
                    hoardingSwiper.autoplay.start();
                }

                const nowRunning = hoardingSwiper.autoplay.running;
                playBtn.setAttribute('aria-pressed', nowRunning ? 'true' : 'false');
                playBtn.setAttribute('aria-label',
                    nowRunning ? 'Pause automatic sliding' : 'Start automatic sliding');
                playBtn.querySelector('i').className = nowRunning ? 'fas fa-pause' : 'fas fa-play';
            });
        }

    });
</script>
