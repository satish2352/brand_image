@extends('website.layout')

@section('title', 'About')

@section('content')

    <style>
        /* LEFT BLOCK */
        .mission-left {
            height: 500px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);

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
            background: #FFFFFF;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .mission-card i {
            font-size: 26px;
            color: #F97316;
            flex-shrink: 0;
        }

        /* MOBILE FIXES */
        @media (max-width: 767px) {


            .mission-left {
                height: 220px !important;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);

            }


            .mission-title {
                font-size: 28px;
            }

            .mission-desc {
                font-size: 17px;
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

    <!-- breadcrumb-section -->
    <div class="container-fluid about-banner-img g-0" data-aos="fade-in">
        <div class="row g-0">
            <!-- Desktop Image -->
            <div class="col-md-12 d-none d-md-block">
                <img src="{{ asset('assets/img/about_us_desktop.webp') }}" alt="About Banner" class="img-fluid">
            </div>

            <!-- Mobile Image -->
            <div class="col-md-12 d-block d-md-none">
                <img src="{{ asset('assets/img/about_us_mobile.webp') }}" alt="About Banner" class="img-fluid">
            </div>
        </div>

        {{-- Heading over the pale left half of the artwork. --}}
        <div class="about-banner-copy">
            <div class="container">
                <span class="about-banner-eyebrow">Who We Are</span>
                <h1 class="about-banner-title">About <span class="accent">Brand Adda</span></h1>
                <!-- <p class="about-banner-sub">Maharashtra&rsquo;s outdoor media platform</p> -->
            </div>
        </div>
    </div>

    <!-- end breadcrumb section -->

    <section class="about-modern about-modern-plain">
        <div class="container">

            <div class="row align-items-center justify-content-center">
                <div class="col-lg-11 py-lg-3" data-aos="fade-up">

                    <span class="about-subtitle">ABOUT US</span>

                    <h2 class="about-title">
                        Powering The Future Of
                        <span>Outdoor Advertising</span>
                    </h2>
                </div>
                <!-- LEFT CONTENT -->
                <div class="col-lg-6 px-4 py-2">


                   
                    <p>
                        Brand Adda is a technology-driven OOH media platform that brings fragmented outdoor advertising 
                        inventory onto one searchable ecosystem, helping brands and agencies discover, plan and execute 
                        campaigns faster, more efficiently and with greater transparency.
                    </p>

                    <p>
                        Brand Adda is being built to create a connected OOH ecosystem where brands, agencies, media 
                        owners and local OOH partners can discover opportunities, plan campaigns and grow their 
                        business through one technology-driven platform.
                    </p>



                    

                    <p>
                        Our objective is to make outdoor media more accessible, efficiently planned and better 
                        utilized across urban, semi-urban and rural markets.
                    </p>
                    {{-- <a href="#" class="about-btn">Learn More About Us</a> --}}
                </div>

                <!-- RIGHT STATS -->
                <div class="col-lg-5">
                    <div class="stats-grid">

                        <div class="stat-card stat-card-years">
                            {{-- The figure and its caption are drawn into the artwork, as on the
                                 home page; the wording stays for screen readers and search. --}}
                            <span class="visually-hidden">12+ Years Experience</span>
                        </div>

                        <div class="stat-card stat-card-clients">
                            {{-- The figure and its caption are drawn into the artwork, as on the
                                 home page; the wording stays for screen readers and search. --}}
                            <span class="visually-hidden">300+ Happy Clients</span>
                        </div>

                        <div class="stat-card stat-card-cities">
                            {{-- The figure and its caption are drawn into the artwork, as on the
                                 home page; the wording stays for screen readers and search. --}}
                            <span class="visually-hidden">50+ Cities Covered</span>
                        </div>

                        <div class="stat-card stat-card-campaigns">
                            {{-- The figure and its caption are drawn into the artwork, as on the
                                 home page; the wording stays for screen readers and search. --}}
                            <span class="visually-hidden">500+ Campaigns</span>
                        </div>

                    </div>
                </div>

                <div class="col-lg-11 py-lg-3 p-3">
                     <h3>Brands and Agencies</h3>
                    <p>Brand Adda enables brands and agencies to discover relevant outdoor media, create campaign 
                        plans and access a wider OOH inventory through a single platform. This provides greater 
                        choice, faster planning and the opportunity to build campaigns more efficiently and cost-effectively.
                    </p>

                     <h3>Media Owners</h3>
                    <p>Brand Adda helps media owners increase the utilization
                        
                    and booking frequency of their outdoor 
                        media. Through upcoming and real-time availability, media owners can showcase inventory that 
                        will become available in the coming days or weeks, allowing brands and agencies to plan and 
                        book media in advance. Our long-term objective is to help media owners maximize their 365-day 
                        booking potential, reduce gaps between campaigns and improve overall media utilization.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= PURPOSE : mission & vision =================
         The billboard and the decorative shapes are part of the artwork, so the
         middle column is deliberately empty on desktop and the copy and cards
         sit either side of it. --}}
    <section class="bi-purpose">
        <div class="container">
            <div class="row bi-purpose-row align-items-center">

                <div class="col-lg-4 bi-purpose-copy" data-aos="fade-up">
                    <span class="bi-purpose-eyebrow"><span aria-hidden="true"></span> Our Purpose</span>
                    <h2 class="bi-purpose-title">
                        Shaping the<br>
                        <span class="accent">Outdoor Advertising</span><br>
                        Landscape
                    </h2>
                    <p class="bi-purpose-sub">
                        Our vision and mission to set new standards in outdoor media excellence.
                    </p>
                </div>

                {{-- Holds the column the artwork's billboard occupies. --}}
                <div class="col-lg-4 d-none d-lg-block" aria-hidden="true"></div>

                {{-- Stacked layouts get the visual as a real element between the copy
                     and the cards, cropped to the billboard band of the portrait
                     artwork. Decorative, so it carries an empty alt. --}}
                <div class="col-12 d-lg-none">
                    <img class="bi-purpose-visual" src="{{ asset('assets/img/vis_mis_mobile.png') }}" alt=""
                        loading="lazy" decoding="async">
                </div>

                <div class="col-lg-4">
                    <ol class="bi-purpose-cards">
                        <li data-aos="fade-up" data-aos-delay="100">
                            <span class="bi-purpose-num" aria-hidden="true">01</span>
                            <span class="bi-purpose-ico"><i class="bi bi-flag-fill" aria-hidden="true"></i></span>
                            <div class="bi-purpose-body">
                                <span class="bi-purpose-k">Mission</span>
                                <h3>Build a Smarter<br>OOH Ecosystem</h3>
                                <p>
                                    To build a technology-driven Outdoor Media ecosystem that is smarter,
                                    simpler and more accessible across India.
                                </p>
                                <spanaria-hidden="true"></span>
                            </div>
                        </li>

                        <li data-aos="fade-up" data-aos-delay="200">
                            <span class="bi-purpose-num" aria-hidden="true">02</span>
                            <span class="bi-purpose-ico"><i class="bi bi-eye-fill" aria-hidden="true"></i></span>
                            <div class="bi-purpose-body">
                                <span class="bi-purpose-k">Vision</span>
                                <h3>Make OOH<br>More Accessible</h3>
                                <p>
                                    To make Outdoor Advertising more accessible, efficient, transparent and
                                    productive through technology for every participant in the OOH ecosystem.
                                </p>
                                <span  aria-hidden="true"></span>
                            </div>
                        </li>
                    </ol>
                </div>

            </div>
        </div>
    </section>


    <!-- end featured section -->

    {{-- ================= TESTIMONIALS =================
         The rotating quote is still driven by the same script: #t-img, #t-name
         and #t-content are read by renderTestimonial(), and the arrows still
         call prevTestimonial() / nextTestimonial(). --}}
    <section class="bi-quotes">
        <div class="container">

            <div class="bi-quotes-head" data-aos="fade-up">
                <span class="bi-eyebrow">Testimonials</span>
                <h2 class="bi-section-title">Trusted by our <span class="accent">Customers</span></h2>
                <p class="bi-quotes-sub">
                    What brands and agencies say about working with us.
                </p>
            </div>

            <div class="bi-quotes-body" data-aos="fade-up" data-aos-delay="100">

                {{-- A quote mark in a filled disc, not a portrait: the panel
                     rotates through three people and the photographs were
                     doing nothing the name below does not already say. --}}
                <span class="bi-quotes-mark" aria-hidden="true">&ldquo;</span>

                <figure class="bi-quotes-figure">
                    <blockquote id="t-content"></blockquote>

                    <figcaption>
                        <span id="t-name" class="bi-quotes-name"></span>
                        <span class="stars" aria-label="Rated 5 out of 5">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                    </figcaption>
                </figure>
            </div>

            <div class="bi-quotes-nav">
                <button type="button" class="arrow" onclick="prevTestimonial()" aria-label="Previous testimonial">&lsaquo;</button>
                <button type="button" class="arrow" onclick="nextTestimonial()" aria-label="Next testimonial">&rsaquo;</button>
            </div>

        </div>
    </section>

    <!-- FEATURED SECTION -->
    {{-- ================= CORE USP =================
         The laptop and the billboards are part of the artwork, so the right
         column is deliberately empty on desktop and the copy sits to its left. --}}
    <section class="bi-usp">
        <div class="container">
            <div class="row">

                <div class="col-lg-6 bi-usp-copy" data-aos="fade-up">
                    <!-- <span aria-hidden="true"></span> -->
                    <span class="bi-purpose-eyebrow"><span aria-hidden="true"></span>OUR COMPETITIVE EDGE</span>

                    <h2 class="bi-usp-title">Our <span class="accent">Core USP</span></h2>
                    <p class="bi-usp-lead">
                        Brand Adda is not just a hoarding listing website. It is being built as a
                        technology-driven OOH media discovery, planning, marketplace and execution
                        ecosystem designed to create value for brands, agencies, media owners and
                        the wider OOH industry.
                    </p>

                    {{-- Stacked layouts get the laptop as a real element between the copy
                         and the cards, cropped to the band it occupies in the portrait
                         artwork. Decorative, so the alt is empty. --}}
                    <img class="bi-usp-visual d-lg-none" src="{{ asset('assets/img/our_usp_mobile.webp') }}" alt=""
                        loading="lazy" decoding="async">

                    <ul class="bi-usp-grid">
                        <li>
                            <span class="bi-usp-ico"><i class="bi bi-pin-map-fill" aria-hidden="true"></i></span>
                            <h3>One Platform</h3>
                  
                        </li>
                        <li>
                            <span class="bi-usp-ico"><i class="bi bi-stack" aria-hidden="true"></i></span>
                            <h3>Complete OOH Inventory</h3>
                            
                        </li>
                        <li>
                            <span class="bi-usp-ico"><i class="bi bi-lightbulb-fill" aria-hidden="true"></i></span>
                            <h3>Smarter Planning</h3>
                           
                        </li>
                        <li>
                            <span class="bi-usp-ico"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
                            <h3>Maximum Media Utilization</h3>
                            
                        </li>
                    </ul>
                </div>

                {{-- Holds the half of the row the artwork occupies. --}}
                <div class="col-lg-6 d-none d-lg-block" aria-hidden="true"></div>

            </div>

           
        </div>
    </section>
    <!-- END FEATURED SECTION -->
    {{-- ================= THE WAY WE DELIVER ================= --}}
    <section class="bi-deliver">
        <div class="container">
            <div class="row align-items-center bi-deliver-row">

                <div class="col-lg-4 bi-deliver-copy" data-aos="fade-up">
                    <span class="bi-deliver-eyebrow"><span aria-hidden="true"></span> Our Services</span>
                    <h2 class="bi-deliver-title">The Way We <span class="accent">Deliver</span></h2>
                    <p class="bi-deliver-sub">
                        Making Outdoor Media simple, transparent & accessible.
                    </p>
                
                </div>

                {{-- Stacked layouts get the artwork as a real element between the copy
                     and the cards, cropped to the band it occupies in the portrait
                     file. Decorative, so the alt is empty. --}}
                <div class="col-12 d-lg-none">
                    <img class="bi-deliver-visual" src="{{ asset('assets/img/services_mobile.webp') }}" alt=""
                        loading="lazy" decoding="async">
                </div>

                <div class="col-lg-8">
                    <ul class="bi-deliver-grid">
                        <li data-aos="fade-up" data-aos-delay="100">
                            <span class="bi-deliver-ico"><i class="bi bi-search" aria-hidden="true"></i></span>
                            <h3>Explore</h3>
                            <p>Find hoardings, billboards and outdoor media locations across Maharashtra</p>
                        </li>

                        <li data-aos="fade-up" data-aos-delay="150">
                            <span class="bi-deliver-ico"><i class="bi bi-bar-chart" aria-hidden="true"></i></span>
                            <h3>Compare</h3>
                            <p>Compare locations, sizes, rates, visibility and other media details in one place.</p>
                        </li>

                        <li data-aos="fade-up" data-aos-delay="200">
                            <span class="bi-deliver-ico"><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
                            <h3>Book</h3>
                            <p>Select your preferred media location and submit your campaign requirements.</p>
                        </li>

                        <li data-aos="fade-up" data-aos-delay="250">
                            <span class="bi-deliver-ico"><i class="bi bi-truck" aria-hidden="true"></i></span>
                            <h3>Deliver</h3>
                            <p>We coordinate the campaign process and provide installation/reporting support.</p>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </section>

    {{-- ================= CLIENT LOGOS =================
         Eyebrow, heading and subtext as everywhere else, with the marks in a
         plain row beneath. Same five images as before. --}}
    <!-- <section class="bi-logos">
        <div class="container">


            <div class="bi-logos-bar">
                <div class="bi-logos-head" data-aos="fade-up">
                    <span class="bi-eyebrow">Our Clients</span>
                    <h2 class="bi-section-title">Brands That <span class="accent">Trust Us</span></h2>
                    <p class="bi-logos-sub">
                        Companies across India that have run their outdoor campaigns with us.
                    </p>
                </div>

                {{-- The track holds the list twice: the second copy is what makes the
                     loop seamless, and it is hidden from screen readers so the marks
                     are not announced twice. --}}
                <div class="bi-logos-marquee">
                    <div class="bi-logos-track">
                        <ul class="bi-logos-row">
                            <li>
                                <img src="{{ asset('assets/img/75.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/76.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/77.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/78.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/79.png') }}" alt="" decoding="async">
                            </li>
                        </ul>
                        <ul class="bi-logos-row" aria-hidden="true">
                            <li>
                                <img src="{{ asset('assets/img/75.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/76.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/77.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/78.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/79.png') }}" alt="" decoding="async">
                            </li>
                        </ul>
                        <ul class="bi-logos-row" aria-hidden="true">
                            <li>
                                <img src="{{ asset('assets/img/75.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/76.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/77.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/78.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/79.png') }}" alt="" decoding="async">
                            </li>
                        </ul>
                        <ul class="bi-logos-row" aria-hidden="true">
                            <li>
                                <img src="{{ asset('assets/img/75.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/76.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/77.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/78.png') }}" alt="" decoding="async">
                            </li>
                            <li>
                                <img src="{{ asset('assets/img/79.png') }}" alt="" decoding="async">
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <script>
        const testimonials = [{
                name: "Vaibhav Patil",
                content: "I take great pleasure in writing to acknowledge the excellent experiences we had while working with Brand Adda."
            },
            {
                name: "Sagar Thakare",
                content: "We have been working across the region with Brand Adda for several years and are always impressed by their ‘can do’ attitude, creative ideas and flawless execution."
            },
            {
                name: "Sureka Sarode",
                content: "From start to finish the journey with Brand Adda has been nothing but exceptional. It was an incredible event."
            }
        ];

        let index = 0;

        function renderTestimonial() {
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

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const counters = document.querySelectorAll('.counter');

            const speed = 200; // animation speed

            const startCounter = (counter) => {
                const target = +counter.getAttribute('data-target');
                let count = 0;
                const increment = target / speed;

                const updateCount = () => {
                    count += increment;
                    if (count < target) {
                        counter.innerText = Math.ceil(count);
                        requestAnimationFrame(updateCount);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCount();
            };

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        startCounter(entry.target);
                        observer.unobserve(entry.target); // run only once
                    }
                });
            }, {
                threshold: 0.5 // mobile ke liye important
            });

            counters.forEach(counter => {
                observer.observe(counter);
            });
        });
    </script>


@endsection
