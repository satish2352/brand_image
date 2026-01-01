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

.news-text-box h3 a {
    font-size: 20px;
    font-weight: 600;
    line-height: 1.4;
}

.blog-meta {
    font-size: 14px;
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

	<!-- features list section -->
	<div class="list-section pt-80 pb-80">
		<div class="container">

			<div class="row">
				<div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
					<div class="list-box d-flex align-items-center">
						<div class="list-icon">
							<i class="fas fa-shipping-fast"></i>
						</div>
						<div class="content">
							<h3>Free Shipping</h3>
							<p>When order over $75</p>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
					<div class="list-box d-flex align-items-center">
						<div class="list-icon">
							<i class="fas fa-phone-volume"></i>
						</div>
						<div class="content">
							<h3>24/7 Support</h3>
							<p>Get support all day</p>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="list-box d-flex justify-content-start align-items-center">
						<div class="list-icon">
							<i class="fas fa-sync"></i>
						</div>
						<div class="content">
							<h3>Refund</h3>
							<p>Get refund within 3 days!</p>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
	<!-- end features list section -->

	<!-- Search Bar Section -->
	@include('website.search')
	<!-- end Bar Section -->

	<!-- SERVICES SECTION -->
	{{-- <section class="services-section">
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
	</section> --}}

	<!-- PROCESS SECTION -->
{{-- <section class="process-section">
    <div class="container">

      
        <div class="process-header text-center">
            <span class="process-subtitle">OUR PROCESS</span>
            <h2 class="process-title">How We <span>Work</span></h2>
            <p class="process-desc">
                A seamless 6-step process to take your outdoor advertising
                from concept to city-wide visibility.
            </p>
        </div>

       
        <div class="process-timeline">

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
</section> --}}

	
	<!-- advertisement section -->
	{{-- <div class="abt-section mt-5 mb-150">
		<div class="container">
			<div class="row">
				<div class="col-lg-6 col-md-12">
					<div class="abt-bg">
						<a href="https://www.youtube.com/watch?v=DBLlFWYcIGQ" class="video-play-btn popup-youtube"><i class="fas fa-play"></i></a>
					</div>
				</div>
				<div class="col-lg-6 col-md-12">
					<div class="abt-text">
						<p class="top-sub">Since Year 1999</p>
						<h2>We are <span class="orange-text">Fruitkha</span></h2>
						<p>Etiam vulputate ut augue vel sodales. In sollicitudin neque et massa porttitor vestibulum ac vel nisi. Vestibulum placerat eget dolor sit amet posuere. In ut dolor aliquet, aliquet sapien sed, interdum velit. Nam eu molestie lorem.</p>
						<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sapiente facilis illo repellat veritatis minus, et labore minima mollitia qui ducimus.</p>
						<a href="about.html" class="boxed-btn mt-4">know more</a>
					</div>
				</div>
			</div>
		</div>
	</div> --}}
	<!-- end advertisement section -->
	
	<!-- shop banner -->
	{{-- <section class="shop-banner">
    	<div class="container">
        	<h3>December sale is on! <br> with big <span class="orange-text">Discount...</span></h3>
            <div class="sale-percent"><span>Sale! <br> Upto</span>50% <span>off</span></div>
            <a href="shop.html" class="cart-btn btn-lg">Shop Now</a>
        </div>
    </section> --}}
	<!-- end shop banner -->

	<div class="latest-news pt-150 pb-150">
		<div class="container">

			{{-- Title --}}
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="section-title">    
						<h3><span class="orange-text">Latest</span> Media</h3>
						<p>Premium outdoor media options curated for you</p>
					</div>
				</div>
			</div>

			{{-- Cards --}}
			{{-- <div class="row" id="media-container">
    @include('website.media-home-list', ['mediaList' => $mediaList])
</div> --}}


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
