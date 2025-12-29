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
	
	<!-- advertisement section -->
	<div class="abt-section mt-5 mb-150">
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
	</div>
	<!-- end advertisement section -->
	
	<!-- shop banner -->
	<section class="shop-banner">
    	<div class="container">
        	<h3>December sale is on! <br> with big <span class="orange-text">Discount...</span></h3>
            <div class="sale-percent"><span>Sale! <br> Upto</span>50% <span>off</span></div>
            <a href="shop.html" class="cart-btn btn-lg">Shop Now</a>
        </div>
    </section>
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
			<div class="row">

				@forelse($mediaList as $media)

					<div class="col-lg-3 col-md-4 mb-5">
						<div class="single-latest-news">

							{{-- Image --}}
								<div class="latest-news-bg"
									style="background-image:url('{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}')">
								</div>

							{{-- Content --}}
							<div class="news-text-box">

								<h3>
									<a href="#">
										{{ $media->media_title ?? $media->category_name }}
									</a>
								</h3>

								{{-- Location --}}
								<p class="blog-meta">
									<i class="fas fa-map-marker-alt"></i>
									{{ $media->area_name }}, {{ $media->city_name }}
								</p>

								{{-- Price --}}
								<div class="media-price">
									₹ {{ number_format($media->price, 2) }}
								</div>

								{{-- Actions --}}
								<div class="card-actions">

									<a href="#" class="card-btn read">
										Read More →
									</a>

									@if($media->category_name === 'Hoardings/Billboards')

										@auth('website')
											<a href="{{ route('cart.add', base64_encode($media->id)) }}"
											class="card-btn cart">
												Add to Cart
											</a>
										@else
											<button class="card-btn cart"
													data-bs-toggle="modal"
													data-bs-target="#authModal"
													onclick="setRedirect('{{ route('cart.add', base64_encode($media->id)) }}')">
												Add to Cart
											</button>
										@endauth

									@else
										<a href="{{ route('contact.create') }}"
										class="card-btn contact">
											Contact Us
										</a>
									@endif

								</div>

							</div>
						</div>
					</div>

				@empty
					<div class="col-12 text-center">
						<p class="text-muted">No media available.</p>
					</div>
				@endforelse

			</div>
		</div>
	</div>

	<!-- logo carousel -->
	<div class="logo-carousel-section">
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
	</div>
	<!-- end logo carousel -->
