@extends('website.layout')

@section('title', 'About')

@section('content')

<!-- breadcrumb-section -->
<div class="container-fluid about-banner-img g-0">
    <div class="row">
        <!-- Desktop Image -->
        <div class="col-md-12 d-none d-md-block">
            <img src="{{ asset('assets/img/about_us.png') }}" alt="About Banner" class="img-fluid">
        </div>

        <!-- Mobile Image -->
        <div class="col-md-12 d-block d-md-none">
            <img src="{{ asset('assets/img/mobile_about_us.png') }}" alt="About Banner" class="img-fluid">
        </div>
    </div>
</div>

<!-- end breadcrumb section -->

<section class="about-modern">
    <div class="container">

        <div class="row align-items-center justify-content-center">
            <div class="col-lg-11 py-lg-3">

                <span class="about-subtitle">ABOUT US</span>

                <h2 class="about-title">
                    Business Has Only Two Functions:
                    <span>Marketing & Innovation</span>
                </h2>
            </div>
            <!-- LEFT CONTENT -->
            <div class="col-lg-6 px-4 py-2">


                <p>
                    Brand Image Media Pvt Ltd is a highly skilled and dynamic advertising agency that specializes in assisting clients in reaching their target audiences through innovative and customized solutions. With our team of experienced professionals, we take great pride in our ability to comprehend your specific needs and offer creative services that can truly make a difference
                </p>

                <p>
                    With over 12 years of valuable experience in the field of Printing, Branding, Advertising, and Events, we have gained extensive knowledge and expertise in executing successful campaigns. Our agency, Brand Image Advertising, has been recognized with prestigious awards for our compelling advertising campaigns.
                </p>


                <p>
                    At Brand Image Media Pvt Ltd, we understand the importance of creating a strong brand image for your business. Through our expertise in branding, we can help you establish a distinct and memorable identity that resonates with your target market. Whether it's designing logos, developing brand guidelines, or creating comprehensive brand strategies, our team is dedicated to delivering exceptional results.
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

            <div class="col-lg-11 py-lg-3 p-3">
                <p>In addition to branding, we excel in various advertising mediums. From traditional print advertisements to digital marketing campaigns, we stay up-to-date with the latest trends and technologies to ensure maximum impact for your message. Our advertising solutions are tailored to your specific goals, whether it's increasing brand awareness, driving website traffic, or generating leads.
                    Furthermore, our proficiency in event management allows us to create memorable experiences that leave a lasting impression. From corporate conferences to product launches, we handle every aspect of event planning, ensuring seamless execution and exceptional attendee experiences.
                </p>
                <p>At Brand Image Media Pvt Ltd, we prioritize client satisfaction and strive to build long-lasting partnerships. We believe in effective communication, collaborative teamwork, and attention to detail, which are the pillars of our successful projects. We are committed to delivering results that exceed your expectations and contribute to your business growth.

                    Thank you for choosing Brand Image Media Pvt Ltd as your advertising partner. We look forward to working with you and helping you achieve your marketing objectives with our expertise and creativity.</p>
            </div>
        </div>
    </div>
</section>

<section class="vision_mission  mt-5">
    <div class="container">
        <h2 class="text-center vision_mission_heading">Shaping the Outdoor Advertising Landscape</h2>
        <p class="text-center vision_mission_para">Our vision and mission to set new standards in outdoor media
            excellence</p>

    </div>
</section>

<style>
    /* LEFT BLOCK */
    .mission-left {
        height: 500px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, .08);

    }



    .mission-title {
        font-size: 40px;
        font-weight: 700;
    }

    .mission-desc {
        font-size: 16px;
                    text-align: justify;

        line-height: 1.6;
    }

    /* RIGHT CARDS */
    .mission-card {
        background: #fff;
        padding: 20px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .mission-card i {
        font-size: 26px;
        color: #ff8c00;
        flex-shrink: 0;
    }

    /* MOBILE FIXES */
    @media (max-width: 767px) {


        .mission-left {
            height: 220px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);

        }


        .mission-title {
            font-size: 28px;
        }

        .mission-desc {
            font-size: 15px;
            text-align: justify;
        }

        .mission-card {
            flex-direction: column;
            text-align: center;
            align-items: center;
        }

        .mission-card h5 {
            margin-top: 10px;
        }
    }

    .misiionsimg {
        height: 100px;
        width: 100px;
    }
</style>

<section class="mission-section py-5">
    <div class="container">
        <div class="row g-4 align-items-center">

            <!-- LEFT -->
            <div class="col-lg-6">

                <img src="{{ asset('assets/img/45.png') }}" class="img-fluid rounded-5 mission-left" alt="mission">


            </div>

            <!-- RIGHT -->
            <div class="col-lg-6">
                <div class="row g-4 align-items-center d-grid">


                    <div class="col-md-12">
                        <div class="mission-card">
                            <img src="{{ asset('assets/img/17.png') }}" class="img-fluid rounded-5 misiionsimg" alt="mission">
                            <div>
                                <h5>Mission</h5>
                                <p class="mission-desc mb-0">
                                    To help businesses achieve their marketing goals through impactful advertising by combining strategic thinking, creative ideas, and modern technology to deliver measurable and meaningful results.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mission-card">
                            <img src="{{ asset('assets/img/15.png') }}" class="img-fluid rounded-5 misiionsimg" alt="mission">
                            <div>
                                <h5>Vision</h5>
                                <p class="mission-desc mb-0">
                                    To become a trusted advertising partner by shaping powerful brand experiences through innovation, creativity, and technology while setting new standards for marketing excellence. </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mission-card">
                            <img src="{{ asset('assets/img/16.png') }}" class="img-fluid rounded-5 misiionsimg" alt="mission">
                            <div>
                                <h5> Values</h5>
                                <p class="mission-desc mb-0">
                                    We value innovation, creativity, and strategic thinking, delivering impactful outdoor media solutions with integrity, transparency, and a strong focus on measurable results.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
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
<section class="py-5 deliver ">
    <div class="py-lg-5 container text-center">

        <h2 class="pb-3">
            The <span class="orange-text"> Way We Deliver</span>
        </h2>
        <p class="mb-5 text-muted vision_mission_para">
            Insight-driven planning and smart execution
            that brings your brand to the forefront.
        </p>

        <div class="row  justify-content-center">

            <!-- CARD 1 -->
            <div class="col-lg-3 col-md-6 col-sm-12 px-md-3 pb-5 pb-md-0  ">
                <div class="info-card orange-card h-100 w-100 ">

                    <div class="content">
                        <img src="{{ asset('assets/img/13.png') }}" style="height: 120px; width: 120px" class="mb-3" alt="">

                        <h5 class="title">Client Brief</h5>
                        <p>A client brief is a document that outlines the requirements and scope of a project or campaign as set forth by a client.</p>

                    </div>
                    <div class="diamond"><span>01</span></div>
                </div>
            </div>

            <!-- CARD 2 -->
            <div class="col-lg-3 col-md-6 col-sm-12 px-md-3 pb-5 pb-md-0 ">
                <div class="info-card orange-card h-100 w-100 ">

                    <div class="content">
                        <img src="{{ asset('assets/img/12.png') }}" style="height: 120px; width: 120px" class="mb-3" alt="">

                        <h5 class="title">Brainstorming</h5>
                        <p>Brainstorming is a general technique for coming up with an idea, but sometimes it's not good enough to just sit down and tell yourself you're going to brainstorm until you get an idea.</p>
                    </div>
                    <div class="diamond"><span>02</span></div>
                </div>
            </div>

            <!-- CARD 3 -->
            <div class="col-lg-3 col-md-6 col-sm-12 px-md-3 pb-5 pb-md-0 ">
                <div class="info-card orange-card h-100 w-100">

                    <div class="content">
                        <img src="{{ asset('assets/img/20.png') }}" style="height: 120px; width: 120px" class="mb-3" alt="">

                        <h5 class="title">Present</h5>
                        <p>Discuss the common demographics of the market, where people shop and how they spend, all of which you will use to justify your creative ideas.</p>
                    </div>
                    <div class="diamond"><span>03</span></div>
                </div>
            </div>

            <!-- CARD 4 -->
            <div class="col-lg-3 col-md-6 col-sm-12 px-md-3 pb-5 pb-md-0 ">
                <div class="info-card orange-card h-100 w-100">
                    <div class="content">
                        <img src="{{ asset('assets/img/11.png') }}" style="height: 120px; width: 120px" class="mb-3" alt="">

                        <h5 class="title">Deliver</h5>
                        <p>The term Ad Delivery means the delivery of online advertisements or advertising-related services using Ad Reporting Data.</p>

                    </div>
                    <div class="diamond"><span>04</span></div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- logo carousel -->
<div class=" ">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="logo-carousel-inner">
                    <div class="single-logo-item">

                        <img src="{{ asset('assets/img/company-logos/1.png') }}" alt="">

                    </div>
                    <div class="single-logo-item">
                        <img src="{{ asset('assets/img/company-logos/2.png') }}" alt="">
                    </div>
                    <div class="single-logo-item">
                        <img src="{{ asset('assets/img/company-logos/3.png') }}" alt="">
                    </div>
                    <div class="single-logo-item">
                        <img src="{{ asset('assets/img/company-logos/4.png') }}" alt="">
                    </div>
                    <div class="single-logo-item">
                        <img src="{{ asset('assets/img/company-logos/5.png') }}" alt="">
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