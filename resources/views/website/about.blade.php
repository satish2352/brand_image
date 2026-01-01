@extends('website.layout')

@section('title', 'Home')

@section('content')

<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>We sale fresh fruits</p>
						<h1>About Us</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- featured section -->
	{{-- <div class="feature-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-7">
					<div class="featured-text">
						<h2 class="pb-3">Why <span class="orange-text">Fruitkha</span></h2>
						<div class="row">
							<div class="col-lg-6 col-md-6 mb-4 mb-md-5">
								<div class="list-box d-flex">
									<div class="list-icon">
										<i class="fas fa-shipping-fast"></i>
									</div>
									<div class="content">
										<h3>Home Delivery</h3>
										<p>sit voluptatem accusantium dolore mque laudantium, totam rem aperiam, eaque ipsa quae ab illo.</p>
									</div>
								</div>
							</div>
							<div class="col-lg-6 col-md-6 mb-5 mb-md-5">
								<div class="list-box d-flex">
									<div class="list-icon">
										<i class="fas fa-money-bill-alt"></i>
									</div>
									<div class="content">
										<h3>Best Price</h3>
										<p>sit voluptatem accusantium dolore mque laudantium, totam rem aperiam, eaque ipsa quae ab illo.</p>
									</div>
								</div>
							</div>
							<div class="col-lg-6 col-md-6 mb-5 mb-md-5">
								<div class="list-box d-flex">
									<div class="list-icon">
										<i class="fas fa-briefcase"></i>
									</div>
									<div class="content">
										<h3>Custom Box</h3>
										<p>sit voluptatem accusantium dolore mque laudantium, totam rem aperiam, eaque ipsa quae ab illo.</p>
									</div>
								</div>
							</div>
							<div class="col-lg-6 col-md-6">
								<div class="list-box d-flex">
									<div class="list-icon">
										<i class="fas fa-sync-alt"></i>
									</div>
									<div class="content">
										<h3>Quick Refund</h3>
										<p>sit voluptatem accusantium dolore mque laudantium, totam rem aperiam, eaque ipsa quae ab illo.</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div> --}}

	<!-- FEATURED SECTION -->
<div class="feature-bg py-100">
    <div class="container">
        <div class="row align-items-center gx-5">

            <!-- LEFT CONTENT -->
            <div class="col-lg-6">
                <div class="featured-text">
                    <h2 class="pb-3">
                        Why <span class="orange-text">Brand Image Media</span>
                    </h2>
                    <p class="mb-4 text-muted">
                        We deliver result-oriented outdoor advertising solutions
                        that help brands gain maximum visibility and impact.
                    </p>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="list-box d-flex">
                                <div class="list-icon">
                                    <i class="fas fa-map-marked-alt"></i>
                                </div>
                                <div class="content">
                                    <h3>Pan India Presence</h3>
                                    <p>Extensive outdoor media coverage across major Indian cities.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="list-box d-flex">
                                <div class="list-icon">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div class="content">
                                    <h3>Best Cost Planning</h3>
                                    <p>Optimized pricing strategies to maximize your ROI.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="list-box d-flex">
                                <div class="list-icon">
                                    <i class="fas fa-lightbulb"></i>
                                </div>
                                <div class="content">
                                    <h3>Creative Execution</h3>
                                    <p>Innovative ideas that connect brands with audiences.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="list-box d-flex">
                                <div class="list-icon">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <div class="content">
                                    <h3>End-to-End Support</h3>
                                    <p>Complete campaign management from planning to execution.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- RIGHT IMAGE -->
            <div class="col-lg-6">
                <div class="feature-image-wrapper">
                    <img src="assets/img/one.jpg"
                         alt="Outdoor Advertising" class="img-fluid">
                </div>
            </div>

        </div>
    </div>
</div>
<!-- END FEATURED SECTION -->

	<!-- end featured section -->

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

@endsection