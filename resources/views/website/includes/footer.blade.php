{{-- <footer class="bg-dark text-white text-center py-3 mt-5">
    <p class="mb-0">© {{ date('Y') }} MyWebsite. All Rights Reserved.</p>
</footer> --}}

	<!-- footer -->
	<footer class="premium-footer">
		<div class="container">
			<div class="row footer-top">

				<!-- Brand -->
				<div class="col-lg-4 col-md-6 footer-col">
					<div class="footer-brand">
						<div class="brand-logo">
							<img src="{{ asset('asset/images/website/logo.png') }}" alt="Brand_Image_Logo" style="height: 85px;">
						</div>
						<p>
							India's premier outdoor advertising agency with 12+ years of
							experience in creating impactful brand campaigns.
						</p>

						<div class="footer-social">
							<a href="#"><i class="fab fa-facebook-f"></i></a>
							<a href="#"><i class="fab fa-twitter"></i></a>
							<a href="#"><i class="fab fa-instagram"></i></a>
							<a href="#"><i class="fab fa-linkedin-in"></i></a>
						</div>
					</div>
				</div>

				<!-- Quick Links -->
				<div class="col-lg-2 col-md-6 footer-col">
					<h5 class="footer-title">Quick Links</h5>
					<ul class="footer-links">
						<li><a href="{{ url('/') }}">Home</a></li>
						<li><a href="{{ route('website.about') }}">About</a></li>
						<li><a href="{{ url('/contact-us') }}">Contact</a></li>
					</ul>
				</div>

				<!-- Services -->
				<div class="col-lg-3 col-md-6 footer-col">
					<h5 class="footer-title">Services</h5>
					<ul class="footer-links no-anchor">
						<li>Traditional OOH</li>
						<li>Digital Displays</li>
						<li>Mall Media</li>
						<li>Transit Media</li>
						<li>Airport Branding</li>
						<li>Wall Painting</li>
					</ul>
				</div>

				<!-- Contact -->
				<div class="col-lg-3 col-md-6 footer-col">
					<h5 class="footer-title">Contact Us</h5>

					<ul class="footer-contact">
						<li>
							<i class="bi bi-geo-alt"></i>
							<a href="https://www.google.com/maps/place/Brand+Image+Media+Pvt+Ltd+%7C+Outdoor+Advertising+Agency/@19.9824861,73.7728886,17z/data=!3m1!4b1!4m6!3m5!1s0x3bddeb2094f5d9ff:0x57bf9c97dbf22492!8m2!3d19.9824861!4d73.7754635!16s%2Fg%2F11j1hly41b"
							target="_blank" rel="noopener">
								Brand Image Media Pvt Ltd,<br>
								New Delhi, India
							</a>
						</li>

						<li>
							<i class="bi bi-telephone"></i>
							<a href="tel:+919999999999">+91 99999 99999</a>
						</li>

						<li>
							<i class="bi bi-envelope"></i>
							<a href="mailto:info@brand-image.co.in">
								info@brand-image.co.in
							</a>
						</li>
					</ul>
				</div>

			</div>
		</div>

		<!-- Bottom Bar -->
		<div class="footer-bottom">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-md-12 text-center">
						<p>© 2025 Brand Image Media Pvt Ltd. All rights reserved.</p>
					</div>
				</div>
			</div>
		</div>
	</footer>

	<!-- end footer -->
	

	<script>
	document.addEventListener("DOMContentLoaded", function () {

		const counters = document.querySelectorAll(".counter");
		let started = false;

		function startCounters() {
			if (started) return;
			started = true;

			counters.forEach(counter => {
				const target = +counter.getAttribute("data-target");
				let count = 0;
				const speed = target / 80; // animation smoothness

				const updateCounter = () => {
					count += speed;
					if (count < target) {
						counter.innerText = Math.ceil(count);
						requestAnimationFrame(updateCounter);
					} else {
						counter.innerText = target + "+";
					}
				};

				updateCounter();
			});
		}

		const observer = new IntersectionObserver(entries => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					startCounters();
				}
			});
		}, { threshold: 0.4 });

		observer.observe(document.querySelector(".about-modern"));

	});
	</script>

	<script>
	document.addEventListener("DOMContentLoaded", function () {

		const currentUrl = window.location.href;
		const menuLinks = document.querySelectorAll("#dashboardMenu a");

		menuLinks.forEach(link => {
			if (currentUrl.includes(link.getAttribute("href"))) {
				link.parentElement.classList.add("active");
			}
		});

	});
	</script>
	
	<!-- jquery -->
	<script src="{{ asset('assets/js/jquery-1.11.3.min.js') }}"></script>
	<!-- bootstrap -->
	{{-- <script src="{{ asset('assets/bootstrap/js/bootstrap.min.js') }}"></script> --}}
	<!-- count down -->
	<script src="{{ asset('assets/js/jquery.countdown.js') }}"></script>
	<!-- isotope -->
	<script src="{{ asset('assets/js/jquery.isotope-3.0.6.min.js') }}"></script>
	<!-- waypoints -->
	<script src="{{ asset('assets/js/waypoints.js') }}"></script>
	<!-- owl carousel -->
	<script src="{{ asset('assets/js/owl.carousel.min.js') }}"></script>
	<!-- magnific popup -->
	<script src="{{ asset('assets/js/jquery.magnific-popup.min.js') }}"></script>
	<!-- mean menu -->
	<script src="{{ asset('assets/js/jquery.meanmenu.min.js') }}"></script>
	<!-- sticker js -->
	<script src="{{ asset('assets/js/sticker.js') }}"></script>
	<!-- main js -->
	<script src="{{ asset('assets/js/main.js') }}"></script>

<script>
    function setRedirect(url) {
        sessionStorage.setItem('redirect_after_login', url);
    }
</script>
