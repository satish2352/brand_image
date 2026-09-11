<!-- footer -->
<footer class="premium-footer">
    {{-- container-fluid, not container: the bar reads better with the logo out
         at the page edge rather than indented to the centred measure. --}}
    <div class="container-fluid">
        <div class="footer-bar">

            <a class="footer-mark" href="{{ url('/') }}">
                {{-- Reverse logo: the footer is brand navy and so is the wordmark, so the
                     standard mark would all but disappear on it. --}}
                <img src="{{ asset('assets/img/logo/brand_adda_light.webp') }}" alt="Brand Adda">
            </a>

            <nav class="footer-nav" aria-label="Footer">
                <a href="{{ url('/') }}" class="{{ request()->routeIs('website.home') ? 'active' : '' }}">Home</a>
                <a href="{{ route('website.about') }}"
                    class="{{ request()->routeIs('website.about') ? 'active' : '' }}">About Us</a>
                <a href="{{ url('/contact-us') }}"
                    class="{{ request()->is('contact-us') ? 'active' : '' }}">Contact Us</a>
            </nav>

            <div class="footer-follow">
                <span class="footer-follow-label">Follow Us</span>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <p class="footer-copy">
                &copy; {{ date('Y') }} <a href="https://brand-image.co.in/" target="_blank" rel="noopener">Brand Adda
                    Pvt. Ltd.</a> All rights reserved.
            </p>

        </div>
    </div>
</footer>

<!-- WhatsApp Floating Button -->
<a href="https://wa.me/917770009506" target="_blank" rel="noopener" class="whatsapp-float" title="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>

<style>
    .whatsapp-float {
        position: fixed;
        bottom: 80px;
        right: 28px;
        z-index: 9999;
        /* Orange, not navy: this button floats over the navy footer as well as
           the white page, and only orange stays visible on both. */
        background-color: #F97316;
        color: #FFFFFF;
        border-radius: 50%;
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
        transition: background-color 0.3s, transform 0.3s;
        text-decoration: none;
    }

    .whatsapp-float:hover {
        /* Keeps its orange on hover; only the scale responds. */
        background-color: #F97316;
        transform: scale(1.1);
        color: #FFFFFF;
    }

    .developed-by {
        font-size: 13px;
        color: #ffffff;
    }

    .dev-logo {
        height: 28px;
        width: 28px;
        object-fit: cover;
        vertical-align: middle;
    }
</style>

<!-- end footer -->

{{-- if admin deactive or delete user then run below --}}
@if (session('auto_logout_message'))
    <script>
        // SweetAlert is loaded with defer, so it does not exist yet while this
        // inline script parses — wait for the deferred scripts to have run.
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'warning',
                title: 'Logged Out',
                text: "{{ session('auto_logout_message') }}",
                confirmButtonText: 'OK'
            });
        });
    </script>
@endif

<script>
    $(document).ajaxError(function(event, xhr) {

        if (xhr.status === 401 && xhr.responseJSON?.message) {

            Swal.fire({
                icon: 'warning',
                title: 'Logged Out',
                text: xhr.responseJSON.message,
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = "/";
            });
        }
    });
</script>
{{-- end --}}

{{-- The hoardings slider used to be initialised here as well as in the home
     page itself. Two Swiper instances on one element each ran their own
     autoplay timer and each wrote the wrapper transform, so the track stepped
     by uneven amounts, and this copy also had nextEl/prevEl swapped. The one
     initialiser now lives beside the markup in website/index.blade.php. --}}

{{-- --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {

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
        }, {
            // A share-of-element threshold cannot be met by something taller than
            // the viewport: on a phone the about section runs past 2000px, so it
            // was never 40% visible and the numbers sat at 0. Firing on the
            // stats grid crossing the lower fifth of the screen works at any
            // element height and any viewport.
            threshold: 0,
            rootMargin: "0px 0px -20% 0px"
        });

        const counterTrigger = document.querySelector(".stats-grid") || document.querySelector(".about-modern");
        if (counterTrigger) observer.observe(counterTrigger);

    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const currentUrl = window.location.href;
        const menuLinks = document.querySelectorAll("#dashboardMenu a");

        menuLinks.forEach(link => {
            if (currentUrl.includes(link.getAttribute("href"))) {
                link.parentElement.classList.add("active");
            }
        });

    });
</script>

<script>
    function swapCarouselImages() {
        let isMobile = window.innerWidth <= 768;

        document.querySelectorAll('.carousel-img').forEach(img => {
            let mobile = img.dataset.mobile;
            let desktop = img.dataset.desktop;

            if (isMobile && mobile) {
                img.src = mobile;
            } else {
                img.src = desktop;
            }
        });
    }

    window.addEventListener('load', swapCarouselImages);
    window.addEventListener('resize', swapCarouselImages);
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.auto-hide-alert');

        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.6s ease';
                alert.style.opacity = '0';

                setTimeout(() => {
                    alert.remove();
                }, 600);
            }, 5000); // ⏱ 5 seconds
        });
    });
</script>

<script>
    $(window).on("load", function() {
        setTimeout(function() {
            $(".loader").fadeOut(100); // smooth hide
        }, 400); // 1 second only
    });
</script>

{{-- Everything below used to start with a second copy of jQuery (1.11.3), which
     replaced the jQuery 3 the layout had already loaded in the head — that is
     what the meanmenu version workaround in layout.blade.php is about. One
     jQuery now, loaded once in the head.

     The theme's plugin set went with it. owl.carousel, isotope, magnific-popup,
     countdown and waypoints were all still being downloaded on every page to
     drive elements this site no longer has: nothing renders .homepage-slider,
     .product-lists, .popup-youtube, .time-countdown or anything with a
     .waypoint() call. The sliders here are Swiper and the hero is a Bootstrap 5
     carousel. Together with the duplicate jQuery that is ~204KB of JavaScript
     over six requests, removed with no behaviour change.

     meanmenu (mobile menu) and sticker (sticky header) are kept — those two are
     the only ones whose elements still exist, both in newheader.blade.php. --}}
<!-- mean menu -->
<script src="{{ asset('assets/js/jquery.meanmenu.min.js') }}" defer></script>
<!-- sticker js -->
<script src="{{ asset('assets/js/sticker.js') }}" defer></script>
<!-- main js -->
<script src="{{ asset('assets/js/main.js') }}" defer></script>

<script>
    function setRedirect(url) {
        sessionStorage.setItem('redirect_after_login', url);
    }
</script>

{{-- Scroll animations are decorative, so they must never hold up first paint. --}}
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js" defer></script>
<script>
    // defer means AOS is not defined yet when this runs inline, so wait for it.
    document.addEventListener('DOMContentLoaded', function () {
        if (window.AOS) AOS.init();
    });
</script>
