@extends('website.layout')

@section('title', 'About')

@section('content')

<!-- breadcrumb-section -->
<div class="container-fluid about-banner-img g-0">
    <div class="row">
        <div class="col-md-12">
            <img src="{{ asset('assets/img/about.png') }}" alt="About Banner" class="img-fluid">
        </div>
    </div>
</div>
<!-- end breadcrumb section -->

<section class="about-modern">
    <div class="container">
        <div class="row align-items-center justify-content-center">

            <!-- LEFT CONTENT -->
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

                <p>
                    From traditional billboards to cutting-edge digital displays, from
                    transit advertising to airport branding—we cover every aspect of
                    outdoor advertising to ensure your brand gets the visibility it
                    deserves.
                </p>

                {{-- <a href="#" class="about-btn">Learn More About Us</a> --}}
            </div>

            <!-- RIGHT STATS -->
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

<section class="vision_mission mb-5 mt-5">
    <div class="container">
        <h2 class="text-center vision_mission_heading">Shaping the Outdoor Advertising Landscape</h2>
        <p class="text-center vision_mission_para">Our vision and mission to set new standards in outdoor media
            excellence</p>
        <div class="row">
            <div class="col-md-6 text-center">
                <img src="{{ asset('assets/img/vision.png') }}" class="img-fluid vision_img" alt="vision">
            </div>
            <div class="col-md-6 text-center">
                <img src="{{ asset('assets/img/mission.png') }}" class="img-fluid mission_img" alt="mission">
            </div>
        </div>
    </div>
</section>
<!-- end featured section -->

<section class="testimonial-section">
    <div class="testimonial-wrapper">

        <!-- LEFT CONTENT -->
        <div class="testimonial-left">
            <h2>
                “I would like to say big ‘Thank you’ to Brand Image TEAM for immense effort and support. In addition, I
                have feeling that our future projects are going to be great as well, good luck to the Team.”
            </h2>
        </div>

        <!-- RIGHT CARD -->
        <div class="testimonial-card">

            <h4 class="title">What Our Client Says</h4>

            <div class="profile">
                <img id="t-img" src="" alt="Client">
            </div>

            <h5 id="t-name"></h5>

            <div class="stars">★★★★★</div>

            <p class="content" id="t-content"></p>

            <!-- NAVIGATION -->
            <div class="nav-arrows">
                <span class="arrow" onclick="prevTestimonial()">‹</span>
                <span class="arrow" onclick="nextTestimonial()">›</span>
            </div>

        </div>

    </div>
</section>

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
                    <p class="mb-5 text-muted whybrandimg-para">
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
                    <img src="{{ asset('assets/img/one.png') }}" alt="Outdoor Advertising" class="img-fluid">
                </div>
            </div>

        </div>
    </div>
</div>
<!-- END FEATURED SECTION -->

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

<script>
    const testimonials = [{
            img: "{{ asset('assets/img/testimonials/test1.jpg') }}",
            name: "Vaibhav Patil",
            content: "I take great pleasure in writing to acknowledge the excellent experiences we had while working with Brand Image."
        },
        {
            img: "{{ asset('assets/img/testimonials/test2.jpg') }}",
            name: "Sagar Thakare",
            content: "We have been working across the region with Brand Image for several years and are always impressed by their ‘can do’ attitude, creative ideas and flawless execution."
        },
        {
            img: "{{ asset('assets/img/testimonials/test3.jpg') }}",
            name: "Sureka Sarode",
            content: "From start to finish the journey with Brand Iamge has been nothing but exceptional. It was an incredible event."
        }
    ];

    let index = 0;

    function renderTestimonial() {
        document.getElementById("t-img").src = testimonials[index].img;
        document.getElementById("t-name").innerText = testimonials[index].name;
        document.getElementById("t-content").innerText = testimonials[index].content;
    }

    function nextTestimonial() {
        index = (index + 1) % testimonials.length;
        renderTestimonial();
    }

    function prevTestimonial() {
        index = (index - 1 + testimonials.length) % testimonials.length;
        renderTestimonial();
    }

    // initial load
    renderTestimonial();
</script>

@endsection