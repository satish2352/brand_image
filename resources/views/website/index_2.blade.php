<style>
.single-latest-news {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.news-text-box {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 20px;
}

.news-text-box h3 {
    font-size: 1.7rem;
    font-weight: 600;
    line-height: 1.4;
}

.blog-meta {
    font-size: 16px;
    margin-bottom: 8px;
	color: #6c757d
}

.media-price {
    font-size: 18px;
    font-weight: 700;
    color: #28a745;
    /* margin: 6px 0 10px; */
}

.card-actions {
    margin-top: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-btn {
    padding: 8px 16px;
    font-size: 14px;
    border-radius: 30px;
    font-weight: 600;
}

.card-btn.cart {
    background: #28a745;
    color: #fff;
}

.card-btn.contact {
    background: #ffb100;
    color: #000;
}

.card-btn.read {
    background: transparent;
    color: #f28123;
}

.pricepermonth{
	color: #a0a0a0;
    font-weight: 400;
}
</style>


	<!-- home page slider -->
	<div class="homepage-slider">
		<!-- single home slider -->
		<div class="single-homepage-slider homepage-bg-1">
			<div class="container">
				<div class="row">
					<div class="col-md-12 col-lg-7 offset-lg-1 offset-xl-0">
						<div class="hero-text">
							{{-- <div class="hero-text-tablecell">
								<p class="subtitle">Fresh & Organic</p>
								<h1>Delicious Seasonal Fruits</h1>
								<div class="hero-btns">
									<a href="shop.html" class="boxed-btn">Fruit Collection</a>
									<a href="contact.html" class="bordered-btn">Contact Us</a>
								</div>
							</div> --}}
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
							{{-- <div class="hero-text-tablecell">
								<p class="subtitle">Fresh Everyday</p>
								<h1>100% Organic Collection</h1>
								<div class="hero-btns">
									<a href="shop.html" class="boxed-btn">Visit Shop</a>
									<a href="contact.html" class="bordered-btn">Contact Us</a>
								</div>
							</div> --}}
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
							{{-- <div class="hero-text-tablecell">
								<p class="subtitle">Mega Sale Going On!</p>
								<h1>Get December Discount</h1>
								<div class="hero-btns">
									<a href="shop.html" class="boxed-btn">Visit Shop</a>
									<a href="contact.html" class="bordered-btn">Contact Us</a>
								</div>
							</div> --}}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end home page slider -->

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
							<p>Extensive outdoor media coverage across prime locations in India.</p>
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
							<p>Innovative campaign planning tailored to your brand objectives.</p>
						</div>
					</div>
				</div>

				<!-- Feature 3 -->
				<div class="col-lg-4 col-md-6">
					<div class="list-box d-flex align-items-center">
						<div class="list-icon">
							<i class="fas fa-chart-line"></i>
						</div>
						<div class="content">
							<h3>Proven Results</h3>
							<p>Data-driven execution ensuring maximum visibility and ROI.</p>
						</div>
					</div>
				</div>

			</div>

		</div>
	</div>
	<!-- END FEATURES SECTION -->

	<!-- Search Bar Section -->
	@include('website.search')
	<!-- end Bar Section -->
	<div class="container">
<div class="row" id="media-container">
    @include('website.media-home-list', ['mediaList' => $mediaList])
</div>
	</div>
<div class="text-center my-4" id="lazy-loader" style="display:none;">
    <span class="spinner-border text-warning"></span>
</div>

<script>
let page = 1;
let loading = false;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(window).on('scroll', function () {

    if (loading) return;

    if ($(window).scrollTop() + $(window).height() >= $('#lazy-loader').offset().top - 200) {

        loading = true;
        page++;

        $('#lazy-loader').show();

        // ✅ ALWAYS serialize search form
        let formData = $('#searchForm').serialize();

        $.ajax({
            url: "{{ route('website.search') }}?page=" + page,
            type: "POST",
            data: formData,
            success: function (html) {

                if ($.trim(html) === '') {
                    $('#lazy-loader').html('No more media');
                    $(window).off('scroll');
                    return;
                }

                $('#media-container').append(html);
                loading = false;
                $('#lazy-loader').hide();
            },
            error: function () {
                loading = false;
                $('#lazy-loader').hide();
            }
        });
    }
});
</script>
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

	<!-- PROCESS SECTION -->
	<section class="process-section">
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

				<div class="col-lg-6">
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
 

	<div class="latest-news pt-150">
        <div class="container">
 
            {{-- Title --}}
            <div class="row">
                <div class="col-lg-8 offset-lg-2 text-center">
                    <div class="section-title">    
                        <h3><span class="orange-text">Hoardings / Billboards</h3>
                        <p>Premium outdoor media options curated for you</p>
                    </div>
                </div>
            </div>
            {{-- ================= HOARDINGS SECTION ================= --}}
            <div class="row mb-5">
				<?php
					// dd($mediaList);
					// die();
					?>
                @foreach($mediaList as $media)
                    @if($media->category_id === 1)
 
                        <div class="col-lg-4 col-md-6 mb-5">
                            <div class="single-latest-news">
 
                                <div class="latest-news-bg"
                                    style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')">
                                </div>
 
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
 
                                    {{-- href="https://www.google.com/maps/search/?api=1&query={{ urlencode($media->area_name . ', ' . $media->city_name) }}" --}}
                                    <div class="media-map mt-4">
                                        <a href="https://www.google.com/maps"
                                        target="_blank"
                                        class="text-muted d-inline-flex align-items-center gap-1">
                                            <img src="{{ asset('assets/img/map.png') }}" width="30">
                                            <span>View on Map</span>
                                        </a>
                                    </div>
									@php
										$isBillboard = ((int) $media->category_id === 1);
										$isBooked    = (int) ($media->is_booked ?? 0);
									@endphp
									<div class="card-actions">

										{{-- ================= BILLBOARDS ================= --}}
										@if($isBillboard)

											{{-- USER LOGGED IN --}}
											@auth('website')

												{{-- NOT BOOKED --}}
												@if($isBooked === 0)
													<a href="{{ route('website.media-details', base64_encode($media->id)) }}"
													class="card-btn read">
														Read More →
													</a>

													<a href="{{ route('cart.add', base64_encode($media->id)) }}"
													class="btn card-btn cart">
														Add to Cart
													</a>
												@else
													{{-- BOOKED --}}
													<a href="{{ route('website.media-details', base64_encode($media->id)) }}"
													class="card-btn read">
														Read More →
													</a>
												@endif

												{{-- USER NOT LOGGED IN --}}
												@else

													@if($isBooked === 1)
														{{-- BOOKED → ONLY READ MORE --}}
														<a href="{{ route('website.media-details', base64_encode($media->id)) }}"
														class="card-btn read">
															Read More →
														</a>
													@else
														{{-- NOT BOOKED → ADD TO CART (LOGIN MODAL) --}}
														<button class="btn card-btn cart"
																data-bs-toggle="modal"
																data-bs-target="#authModal"
																onclick="setRedirect('{{ route('cart.add', base64_encode($media->id)) }}')">
															Add to Cart
														</button>
													@endif

												@endauth


										{{-- ================= OTHER MEDIA ================= --}}
										@else
											{{-- ONLY CONTACT US (NO READ MORE) --}}
											<a href="{{ route('contact.create') }}"
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
    </div>

	<div class="latest-news pb-150">
		<div class="container">

			{{-- Title --}}
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="section-title">    
						<h3><span class="orange-text">Other Media</h3>
						<p>Premium outdoor media options curated for you</p>
					</div>
				</div>
			</div>
			{{-- ================= OTHER MEDIA SECTION ================= --}}
			<div class="row">

				@foreach($mediaList as $media)
					@if($media->category_name !== 'Hoardings/Billboards')

						<div class="col-lg-4 col-md-6 mb-5">
							<div class="single-latest-news">

								<div class="latest-news-bg"
									style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')">
								</div>

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

									<div class="media-map mt-4">
										<a href="https://www.google.com/maps"
										target="_blank"
										class="text-muted d-inline-flex align-items-center gap-1">
											<img src="{{ asset('assets/img/map.png') }}" width="30">
											<span>View on Map</span>
										</a>
									</div>
									@php
										$isBillboard = ((int) $media->category_id === 1);
										$isBooked    = (int) ($media->is_booked ?? 0);
									@endphp
									<div class="card-actions">

										{{-- ================= BILLBOARDS ================= --}}
										@if($isBillboard)

											{{-- USER LOGGED IN --}}
											@auth('website')

												{{-- NOT BOOKED --}}
												@if($isBooked === 0)
													<a href="{{ route('website.media-details', base64_encode($media->id)) }}"
													class="card-btn read">
														Read More →
													</a>

													<a href="{{ route('cart.add', base64_encode($media->id)) }}"
													class="btn card-btn cart">
														Add to Cart
													</a>
												@else
													{{-- BOOKED --}}
													<a href="{{ route('website.media-details', base64_encode($media->id)) }}"
													class="card-btn read">
														Read More →
													</a>
												@endif

												{{-- USER NOT LOGGED IN --}}
												@else

													@if($isBooked === 1)
														{{-- BOOKED → ONLY READ MORE --}}
														<a href="{{ route('website.media-details', base64_encode($media->id)) }}"
														class="card-btn read">
															Read More →
														</a>
													@else
														{{-- NOT BOOKED → ADD TO CART (LOGIN MODAL) --}}
														<button class="btn card-btn cart"
																data-bs-toggle="modal"
																data-bs-target="#authModal"
																onclick="setRedirect('{{ route('cart.add', base64_encode($media->id)) }}')">
															Add to Cart
														</button>
													@endif

												@endauth


										{{-- ================= OTHER MEDIA ================= --}}
										@else
											{{-- ONLY CONTACT US (NO READ MORE) --}}
											<a href="{{ route('contact.create') }}"
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
	</div>
 
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
