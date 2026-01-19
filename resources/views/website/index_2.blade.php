<!-- home page slider -->
{{-- <div class="homepage-slider">
		<!-- single home slider -->
		<div class="single-homepage-slider homepage-bg-1">
			<div class="container">
				<div class="row">
					<div class="col-md-12 col-lg-7 offset-lg-1 offset-xl-0">
						<div class="hero-text">
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- single home slider -->
		<div class="single-homepage-slider homepage-bg-2">
			<div class="container">
				<div class="row">
					<div class="col-lg-10 offset-lg-1 text-center">
						<div class="hero-text">
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- single home slider -->
		<div class="single-homepage-slider homepage-bg-3">
			<div class="container">
				<div class="row">
					<div class="col-lg-10 offset-lg-1 text-right">
						<div class="hero-text">
						</div>
					</div>
				</div>
			</div>
		</div>
	</div> --}}
<!-- end home page slider -->

@if ($sliders->count())

<div id="carouselExampleControls" class="carousel slide" data-bs-ride="carousel">

    <div class="carousel-inner">

        @foreach ($sliders as $key => $slider)
        <div class="carousel-item {{ $key === 0 ? 'active' : '' }}">

            <img src="{{ config('fileConstants.IMAGE_VIEW') . $slider->desktop_image }}"
                class="d-block w-100 carousel-img"
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
{{-- NO SLIDER IMAGE --}}
<div class="d-flex align-items-center justify-content-center"
    style="height: calc(100vh - 90px); background:#f5f5f5;">

    <div class="text-center">
        <i class="bi bi-image text-muted" style="font-size:60px;"></i>
        <h4 class="mt-3 text-muted">No image uploaded</h4>
        <p class="text-muted">Please upload home slider images from admin panel</p>
    </div>

</div>

@endif



<!-- FEATURES SECTION -->
<div class="list-section pt-80 pb-80">
    <div class="container">

        <div class="row">

            <!-- Feature 1 -->
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                <div class="list-box d-flex align-items-center">
                    <div class="list-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="content">
                        <h3>Wide Media Reach</h3>
                        <p>Extensive outdoor media coverage <br>across prime locations in India.</p>
                    </div>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
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
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
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
@include('website.search-form')
<!-- end Bar Section -->
{{-- <div class="container">
<div class="row" id="media-container">
    @include('website.media-home-list', ['mediaList' => $mediaList])
</div>
	</div>
<div class="text-center my-4" id="lazy-loader" style="display:none;">
    <span class="spinner-border text-warning"></span>
</div> --}}


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


        <div class="row g-4">

            <div class="col-lg-3 col-md-6">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-display"></i></div>
                    <h4>Traditional OOH</h4>
                    <p>Billboards, hoardings, and unipoles across prime locations.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-layers"></i></div>
                    <h4>Digital Displays</h4>
                    <p>LED screens and digital billboards with dynamic content.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card">
                    <div class="service-icon highlight"><i class="bi bi-brush"></i></div>
                    <h4>Wall Painting</h4>
                    <p>Large-scale wall murals and paintings for lasting impressions.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-shop"></i></div>
                    <h4>Mall Media</h4>
                    <p>Strategic placements in shopping malls to reach consumers.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-building"></i></div>
                    <h4>Office Branding</h4>
                    <p>Corporate signage and office branding solutions.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-train-front"></i></div>
                    <h4>Transit Media</h4>
                    <p>Bus, metro, and auto advertising for mobile exposure.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-airplane"></i></div>
                    <h4>Airport Branding</h4>
                    <p>Premium airport advertising targeting affluent travelers.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-palette"></i></div>
                    <h4>Wall Wraps</h4>
                    <p>Full building wraps and facade branding for landmarks.</p>
                </div>
            </div>

        </div>
    </div>
</section>

<div class="latest-news pt-150">
    <div class="container">

        {{-- Title --}}
        <div class="row">
            <div class="col-lg-8 offset-lg-2 text-center">
                <div class="section-title hording-section-title">
                    <h3><span class="orange-text">Hoardings / Billboards</h3>
                    <p>Turn busy roads into powerful brand touchpoints with premium hoardings and billboard solutions.
                    </p>
                </div>
            </div>
        </div>
        {{-- ================= HOARDINGS SECTION ================= --}}
        {{-- <div class="row mb-4"> --}}

        <div class="hoarding-slider-wrapper position-relative">

            <!-- Custom Navigation -->
            <div class="custom-swiper-nav">
                <div class="swiper-btn swiper-btn-prev">
                    <i class="fas fa-chevron-left"></i>
                </div>
                <div class="swiper-btn swiper-btn-next">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
        <div class="swiper hoarding-slider mb-4">
            <div class="swiper-wrapper">
                <?php
                // dd($mediaList);
                // die();
                ?>
                @php
                $latestFive = $mediaList->where('category_id', 1)->sortByDesc('created_at')->take(5);
                @endphp
                @foreach ($latestFive as $media)
                @if ($media->category_id === 1)

                {{-- <div class="col-lg-4 col-md-6 mb-5"> --}}
                <div class="swiper-slide">
                    <div class="single-latest-news">

                        {{-- <div class="latest-news-bg"
                                    style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')">
                    </div> --}}
                    @php
                    $isBillboard = (int) $media->category_id === 1;
                    $isBooked = (int) ($media->is_booked ?? 0);
                    $width = (float) ($media->width ?? 0);
                    $height = (float) ($media->height ?? 0);
                    $sqft = $width * $height;
                    @endphp
                    <div class="latest-news-bg"
                        style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')">

                        @if ($isBooked === 1)
                        <span class="media-badge booked">Booked</span>
                        @else
                        <span class="media-badge available">Available</span>
                        @endif

                    </div>

                    <div class="news-text-box">

                        <h3>{{ $media->media_title ?? $media->category_name }}</h3>

                        <p class="blog-meta">
                            <i class="fas fa-map-marker-alt"></i>
                            {{ $media->area_name }}, {{ $media->city_name }}
                        </p>

                        <div class="col-12 mb-2">
                            <strong>Size:</strong>
                            {{ number_format($media->width, 2) }} x {{ number_format($media->height, 2) }} ft

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
                        <div class="media-map mt-4 d-flex align-items-center gap-3">
                            {{-- <a href="https://www.google.com/maps"> --}}
                            {{-- </a> --}}

                            <a href="https://www.google.com/maps?q={{ $media->latitude }},{{ $media->longitude }}"
                                target="_blank" class="text-muted d-inline-flex align-items-center gap-1">
                                <img src="{{ asset('assets/img/map.png') }}" width="30">
                                <span>View on Map</span>
                            </a>
                            @if (!empty($media->video_link))
                                <a href="{{ $media->video_link }}" target="_blank"
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
                            <a href="{{ route('contact.create') }}#contact-form" class="card-btn contact">
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

        <!-- Arrows -->
        {{-- <div class="swiper-button-next"></div>
				<div class="swiper-button-prev"></div> --}}
    </div>
</div>

</div>
</div>

<div class="latest-news pb-150">
    <div class="container">

        {{-- Title --}}
        <div class="row">
            <div class="col-lg-8 offset-lg-2 text-center">
                <div class="section-title">
                    <h3><span class="orange-text">Other Media</h3>
                    <p>Extend your brand reach with strategic non-traditional media that captures attention in everyday
                        environments.</p>
                </div>
            </div>
        </div>
        {{-- ================= OTHER MEDIA SECTION ================= --}}
        <div class="row">

            @foreach ($mediaList as $media)
            @if ($media->category_name !== 'Hoardings/Billboards')

            <div class="col-lg-4 col-md-6 mb-5">
                <div class="single-latest-news">

                    <div class="latest-news-bg"
                        style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')">
                    </div>
                    {{-- <div class="latest-news-bg"
									style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')">

                    @if ($isBooked === 1)
                    <span class="media-badge booked">Booked</span>
                    @else
                    <span class="media-badge available">Available</span>
                    @endif

                </div> --}}

                <div class="news-text-box">

                    <h3>{{ $media->media_title ?? $media->category_name }}</h3>

                    <p class="blog-meta">
                        <i class="fas fa-map-marker-alt"></i>
                        {{ $media->area_name }}, {{ $media->city_name }}
                    </p>

                    <div class="media-price">
                        ₹ {{ number_format($media->price, 2) }}
                        <small class="pricepermonth">/Month</small>
                    </div>

                    {{-- <div class="media-map mt-4">
                        <a href="https://www.google.com/maps?q={{ $media->latitude }},{{ $media->longitude }}"
                            target="_blank" class="text-muted d-inline-flex align-items-center gap-1">
                            <img src="{{ asset('assets/img/map.png') }}" width="30">
                            <span>View on Map</span>
                        </a>
                    </div> --}}
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
                        {{-- <a href="{{ route('contact.create') }}" class="card-btn contact">
                        Contact Us
                        </a> --}}
                        <a href="{{ route('contact.create') }}#contact-form" class="card-btn contact">
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
</div>

<!-- PROCESS SECTION -->
<section class="work-process-section">
    <div class="container">


        <div class="process-header text-center">
            <span class="process-subtitle">OUR PROCESS</span>
            <h2 class="process-title">How We <span>Work</span></h2>
            <p class="process-desc">
                A seamless 6-step process to take your outdoor advertising
                from concept to city-wide visibility.
            </p>
        </div>

        <!-- Process Timeline -->
        <div class="process-timeline justify-content-center align-items-center">

            <div class="timeline-line"></div>

            <div class="process-step">
                <div class="process-circle">
                    <i class="bi bi-search"></i>
                    <span class="step-no">01</span>
                </div>
                <h4>Search</h4>
                <p>Browse our extensive inventory of outdoor media locations across India.</p>
            </div>

            <div class="process-step">
                <div class="process-circle">
                    <i class="bi bi-file-earmark-text"></i>
                    <span class="step-no">02</span>
                </div>
                <h4>Make Plan</h4>
                <p>Create a customized media plan based on your target audience and budget.</p>
            </div>

            <div class="process-step">
                <div class="process-circle">
                    <i class="bi bi-calendar-check"></i>
                    <span class="step-no">03</span>
                </div>
                <h4>Book</h4>
                <p>Reserve your preferred locations and timeframes with easy booking.</p>
            </div>

            <div class="process-step">
                <div class="process-circle">
                    <i class="bi bi-check-circle"></i>
                    <span class="step-no">04</span>
                </div>
                <h4>Approval</h4>
                <p>Get quick approvals and clearances for your campaign materials.</p>
            </div>

            <div class="process-step">
                <div class="process-circle">
                    <i class="bi bi-credit-card"></i>
                    <span class="step-no">05</span>
                </div>
                <h4>Pay</h4>
                <p>Secure and flexible payment options for your convenience.</p>
            </div>

            <div class="process-step">
                <div class="process-circle">
                    <i class="bi bi-rocket-takeoff"></i>
                    <span class="step-no">06</span>
                </div>
                <h4>Go Live</h4>
                <p>Watch your brand come to life across premium outdoor locations.</p>
            </div>

        </div>
    </div>
</section>

<section class="about-modern">
    <div class="container">
        <div class="row align-items-center">


            <div class="col-lg-6">
                <span class="about-subtitle">ABOUT US</span>

                <h2 class="about-title">
                    Business Has Only Two Functions:
                    <span>Marketing & Innovation</span>
                </h2>

                <p>
                    Brand Image Media Pvt Ltd is a highly skilled and dynamic advertising
                    agency that specializes in assisting clients in reaching their target
                    audiences through innovative and customized solutions.
                </p>

                <p>
                    With over 12 years of valuable experience in Printing, Branding,
                    Advertising, and Events, we have gained extensive knowledge and
                    expertise in executing successful campaigns that deliver measurable
                    results.
                </p>

                <a href="{{ route('website.about') }}" class="about-btn">Know More</a>
            </div>

            <div class="col-lg-5">
                <div class="stats-grid">

                    <div class="stat-card">
                        <i class="bi bi-award"></i>
                        <h3 class="counter" data-target="12">0</h3>
                        <span>Years Experience</span>
                    </div>

                    <div class="stat-card">
                        <i class="bi bi-people"></i>
                        <h3 class="counter" data-target="300">0</h3>
                        <span>Happy Clients</span>
                    </div>

                    <div class="stat-card">
                        <i class="bi bi-geo-alt"></i>
                        <h3 class="counter" data-target="50">0</h3>
                        <span>Cities Covered</span>
                    </div>

                    <div class="stat-card">
                        <i class="bi bi-stars"></i>
                        <h3 class="counter" data-target="500">0</h3>
                        <span>Campaigns</span>
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

            <h2>
                Ready to Amplify Your <br>
                <span>Brand Presence?</span>
            </h2>

            <p>
                Let's discuss how we can transform your advertising vision
                into city-wide visibility. Get a free consultation today.
            </p>

            <div class="cta-actions">
                <a href="{{ url('/contact-us') }}" class="btn-cta primary">
                    Get Free Quote <i class="bi bi-arrow-right"></i>
                </a>

                <a href="tel:+9177700 09506" class="btn-cta outline">
                    <i class="bi bi-telephone"></i> Call Us Now
                </a>
            </div>

        </div>
    </div>
</section>

<!-- logo carousel -->
{{-- <div class="logo-carousel-section">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<div class="logo-carousel-inner">
						<div class="single-logo-item">
							<img src="assets/img/company-logos/1.png" alt="">
						</div>
						<div class="single-logo-item">
							<img src="assets/img/company-logos/2.png" alt="">
						</div>
						<div class="single-logo-item">
							<img src="assets/img/company-logos/3.png" alt="">
						</div>
						<div class="single-logo-item">
							<img src="assets/img/company-logos/4.png" alt="">
						</div>
						<div class="single-logo-item">
							<img src="assets/img/company-logos/5.png" alt="">
						</div>
					</div>
				</div>
			</div>
		</div>
	</div> --}}
<!-- end logo carousel -->