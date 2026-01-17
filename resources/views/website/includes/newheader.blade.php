{{-- <style>
    .loader{
    position: fixed;
    inset: 0;

    background: transparent !important;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);

    z-index: 9999;

    display: none;              /* IMPORTANT */
    align-items: center;
    justify-content: center;

    pointer-events: all;
}

</style> --}}

<!--PreLoader-->
<div class="loader">
    <div class="loader-inner">
        <div class="circle"></div>
    </div>
</div>
<!--PreLoader Ends-->

<!-- header -->
<div class="top-header-area" id="sticker">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-sm-12 text-center">
                <div class="main-menu-wrap">
                    <!-- logo -->
                    <div class="site-logo">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('asset/images/website/logo.png') }}" alt="Brand_Image_Logo"
                                style="height: 55px;">
                        </a>
                    </div>
                    <!-- logo -->

                    <!-- menu start -->
                    <nav class="main-menu">
                        <ul>
                            <li class="{{ request()->is('/') ? 'active' : '' }}"><a href="{{ url('/') }}">Home</a>
                            </li>
                            <li class="{{ request()->routeIs('website.about') ? 'active' : '' }}"><a
                                    href="{{ route('website.about') }}">About Us</a></li>
                            <li class="{{ request()->is('contact-us') ? 'active' : '' }}"><a
                                    href="{{ url('/contact-us') }}">Contact Us</a></li>
                            {{-- <li><a href="{{ url('/media-search') }}">Search Media</a></li> --}}
                            {{-- <li>
									<a href="{{ route('campaign.list') }}">
										Campaign List
									</a>
								</li> --}}
                        </ul>
                    </nav>

                    <div class="header-icons new-header-icons">
                        @auth('website')
                            <a href="{{ route('cart.index') }}" class="btn new-btn-light position-relative">
                                <i class="bi bi-cart3 fs-5"></i>
                                @if ($cartCount > 0)
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill cart-count">
                                        {{ $cartCount }}
                                    </span>
                                @endif
                            </a>
                        @else
                            <button class="btn new-btn-light position-relative" onclick="openLoginForCart()">
                                <i class="bi bi-cart3 fs-5"></i>
                                @unless ($cartCount == 0)
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill cart-count">
                                        0
                                    </span>
                                @endunless
                            </button>
                        @endauth
                        {{-- <a class="shopping-cart" href="cart.html"><i class="fas fa-shopping-cart"></i></a> --}}
                        {{-- <a class="mobile-hide search-bar-icon" href="#"><i class="fas fa-search"></i></a> --}}

                        @auth('website')

                            <div class="dropdown user-dropdown">

                                <button class="btn d-flex align-items-center user-btn" data-bs-toggle="dropdown">

                                    <!-- USER NAME + ARROW -->
                                    {{-- <span class="me-2 user-name">{{ session('website')->name }}</span> --}}

                                    <i class="bi bi-caret-down-fill dropdown-arrow"></i>

                                    <!-- USER AVATAR -->
                                    <img src="{{ asset('asset/images/website/user.png') }}" class="user-avatar"
                                        alt="User">
                                </button>

                                <!-- SUPER ATTRACTIVE DROPDOWN -->
                                <ul class="dropdown-menu dropdown-menu-end user-menu-v2">

                                    <!-- USER INFO -->
                                    <li class="user-info">
                                        <strong>{{ Auth::guard('website')->user()->name }}</strong>
                                        <span>{{ Auth::guard('website')->user()->email }}</span>
                                    </li>

                                    <!-- ACTIONS -->
                                    <li class="menu-actions">
                                        <a href="{{ route('dashboard.home') }}" class="menu-btn active">
                                            <i class="bi bi-speedometer2"></i>
                                            Dashboard
                                        </a>

                                        <a href="{{ route('website.logout') }}" class="menu-btn logout">
                                            <i class="bi bi-box-arrow-right"></i>
                                            Logout
                                        </a>
                                    </li>

                                </ul>

                            </div>
                        @else
                            <button class="login-btn btn" data-bs-toggle="modal" data-bs-target="#authModal">
                                <i class="bi-person-circle"></i>
                            </button>

                            @endif
                        </div>



                        {{-- <a class="mobile-show search-bar-icon" href="#"><i class="fas fa-search"></i></a> --}}
                        <div class="mobile-menu"></div>
                        <!-- menu end -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end header -->


    <!-- LOGIN / SIGNUP MODAL -->
    <!-- AUTH MODAL -->
    <div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Account Access</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">

                        <!-- LEFT IMAGE -->
                        {{-- <div class="col-md-5 auth-modal-left">
                        <img src="/images/login-banner.png" alt="Image">
                    </div> --}}

                        <!-- RIGHT SIDE -->
                        <div class="col-md-12 p-4">

                            <!-- LOGIN FORM -->
                            <div id="loginArea">

                                <h4 class="auth-title">Login to Continue</h4>

                                <form method="POST" id="loginForm" novalidate>
                                    @csrf

                                    <div class="mb-3">
                                        <label>Email Address *</label>
                                        <input type="email" name="login_email" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label>Password *</label>
                                        <div class="password-wrapper">
                                            <input type="password" name="login_password"
                                                class="form-control password-field">
                                            <i class="bi bi-eye-slash password-toggle"></i>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-dark w-100">Login</button>

                                    <div class="auth-switch mt-3">
                                        Don't have an account?
                                        <a onclick="showSignup()">Sign Up</a>
                                    </div>
                                </form>
                            </div>

                            <!-- SIGNUP FORM -->
                            <div id="signupArea" style="display:none;">

                                <h4 class="auth-title">Create Your Account</h4>

                                <form id="signupForm" novalidate>
                                    @csrf

                                    <div class="mb-3">
                                        <label>Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="signup_name" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label>Email Id <span class="text-danger">*</span></label>
                                        <input type="email" name="signup_email" id="signupEmail" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label>Mobile Number <span class="text-danger">*</span></label>
                                        <input type="text" name="signup_mobile_number" class="form-control" maxlength="10" inputmode="numeric" autocomplete="off">
                                    </div>

                                    <div class="mb-3">
                                        <label>Organisation (optional)</label>
                                        <input type="text" name="signup_organisation" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label>GST (optional)</label>
                                        <input type="text" name="signup_gst" maxlength="15" class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label>Password *</label>
                                        <div class="password-wrapper">
                                            <input type="password" name="signup_password"
                                                class="form-control password-field">
                                            <i class="bi bi-eye-slash password-toggle"></i>
                                        </div>
                                    </div>

                                    <!-- VERIFY ACCOUNT BUTTON -->
                                    <button type="button" class="btn btn-dark w-100" id="sendOtpBtn">
                                        Verify Account
                                    </button>

                                    <div class="auth-switch mt-3">
                                        Already have an account?
                                        <a onclick="showLogin()">Login</a>
                                    </div>
                                </form>
                            </div>

                            <!-- OTP AREA -->
                            <div id="otpArea" style="display:none;">

                                <h4 class="auth-title">Verify Email</h4>

                                <div class="mb-3">
                                    <label>Email</label>
                                    <input type="email" id="otpEmail" class="form-control" readonly>
                                </div>

                                <div class="mb-2">
                                    <label>Enter OTP</label>
                                    <input type="text" id="otpInput" class="form-control">
                                </div>

                                <small class="text-muted">
                                    OTP expires in <span id="otpTimer">01:00</span>
                                </small>

                                <div class="text-danger mt-2" id="otpError"></div>

                                <button class="btn btn-dark w-100 mt-3" id="verifyOtpBtn">
                                    Verify OTP
                                </button>

                                <button class="btn btn-link w-100 mt-2 d-none" id="resendOtpBtn">
                                    Resend OTP
                                </button>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        function showSignup() {
            document.getElementById('loginArea').style.display = 'none';
            document.getElementById('signupArea').style.display = 'block';
        }

        function showLogin() {
            document.getElementById('signupArea').style.display = 'none';
            document.getElementById('loginArea').style.display = 'block';
        }

        $(document).on("click", ".password-toggle", function() {
            let input = $(this).siblings(".password-field");

            if (input.attr("type") === "password") {
                input.attr("type", "text");
                $(this).removeClass("bi-eye-slash").addClass("bi-eye");
            } else {
                input.attr("type", "password");
                $(this).removeClass("bi-eye").addClass("bi-eye-slash");
            }
        });
    </script>

    <script>
        $(document).ready(function() {

            const nameRegex   = /^[A-Za-z\s]{2,50}$/;  
            const mobileRegex = /^[6-9][0-9]{9}$/; 
            const emailRegex  = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            const gstRegex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;

            $('input[name="signup_name"]').on('input', function () {
                this.value = this.value.replace(/[^A-Za-z\s]/g, '');
            });

            $('input[name="signup_mobile_number"]').on('input', function () {
                this.value = this.value
                    .replace(/[^0-9]/g, '')   // block letters
                    .substring(0, 10);        // max 10 digits
            });

            $('input[name="signup_gst"]').on('input', function () {
                this.value = this.value
                    .toUpperCase()                 // auto uppercase
                    .replace(/[^0-9A-Z]/g, '')     // only A–Z & 0–9
                    .substring(0, 15);             // GST = 15 chars
            });

            /* ---------- SWITCH TABS ---------- */

            window.showSignup = function() {
                $("#loginArea").hide();
                $("#signupArea").show();
            };

            window.showLogin = function() {
                $("#signupArea").hide();
                $("#loginArea").show();
            };


            /* ---------- LOADER FUNCTIONS ---------- */

            function showLoader() {
                $("#globalLoader").attr("aria-hidden", "false");
                $("body").css("overflow", "hidden");
            }

            function hideLoader() {
                $("#globalLoader").attr("aria-hidden", "true");
                $("body").css("overflow", "");
            }


            /* ---------------- LOGIN ---------------- */
            // $("#loginForm").on("submit", function(e) {
            //     e.preventDefault();

            //     $(".text-danger").remove();
            //     showLoader();

            //     $.ajax({
            //         url: "{{ route('website.login') }}",
            //         method: "POST",
            //         data: $(this).serialize(),

            //         success: function(res) {
            //             hideLoader();

            //             // if(res.status){
            //             //     Swal.fire("Success!", res.message, "success")
            //             //     .then(() => window.location.reload());
            //             // } 
            //             if (res.status) {

            //                 let redirectUrl = sessionStorage.getItem('redirect_after_login');

            //                 Swal.fire("Success!", res.message, "success").then(() => {

            //                     if (redirectUrl) {
            //                         sessionStorage.removeItem('redirect_after_login');
            //                         window.location.href = redirectUrl; //  ADD TO CART
            //                     } else {
            //                         window.location.reload(); // normal login
            //                     }

            //                 });
            //             } else {
            //                 // Swal.fire("Error!", res.message, "error");
            //                 $("#loginForm").prepend(
            //                     `<div class="text-danger mb-2">${res.message}</div>`
            //                 );
            //             }
            //         },

            //         error: function(xhr) {
            //             hideLoader();

            //             // If validation error (422)
            //             if (xhr.status === 422) {
            //                 $("#authModal").modal("show");
            //                 showLogin();

            //                 let errors = xhr.responseJSON.errors;
            //                 $.each(errors, function(field, msg) {
            //                     $(`#loginForm [name="${field}"]`).after(
            //                         `<span class="text-danger">${msg[0]}</span>`);
            //                 });

            //             } else {
            //                 // Any other server/API/database error
            //                 Swal.fire(
            //                     "Oops!",
            //                     "Something went wrong. Please try again!",
            //                     "error"
            //                 );
            //             }
            //         }

            //         // error: function(xhr){
            //         //     hideLoader();

            //         //     $("#authModal").modal("show");
            //         //     showLogin();

            //         //     let errors = xhr.responseJSON.errors;
            //         //     $.each(errors, function(field, msg){
            //         //         $(`#loginForm [name="${field}"]`).after(`<span class="text-danger">${msg[0]}</span>`);
            //         //     });
            //         // }
            //     });
            // });

            /* ================= LOGIN VALIDATION + AJAX ================= */
            $("#loginForm").on("submit", function (e) {
                e.preventDefault();

                let valid = true;
                $("#loginForm .text-danger").remove();

                function error(el, msg) {
                    el.after(`<span class="text-danger">${msg}</span>`);
                    valid = false;
                }

                const email = $('[name="login_email"]');
                const pass  = $('[name="login_password"]');

                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

                // EMAIL
                if (!email.val()) {
                    error(email, "Email is required");
                } else if (!emailRegex.test(email.val())) {
                    error(email, "Enter valid email address");
                }

                // PASSWORD
                if (!pass.val()) {
                    error(pass, "Password is required");
                }

                if (!valid) return; // STOP HERE

                // AJAX only if validation passes
                showLoader();

                $.ajax({
                    url: "{{ route('website.login') }}",
                    method: "POST",
                    data: $(this).serialize(),

                    success: function (res) {
                        hideLoader();

                        if (res.status) {
                            let redirectUrl = sessionStorage.getItem('redirect_after_login');

                            Swal.fire("Success!", res.message, "success").then(() => {
                                if (redirectUrl) {
                                    sessionStorage.removeItem('redirect_after_login');
                                    window.location.href = redirectUrl;
                                } else {
                                    window.location.reload();
                                }
                            });
                        } else {
                            $("#loginForm").prepend(
                                `<div class="text-danger mb-2">${res.message}</div>`
                            );
                        }
                    },

                    error: function (xhr) {
                        hideLoader();

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, msg) {
                                $(`#loginForm [name="${field}"]`)
                                    .after(`<span class="text-danger">${msg[0]}</span>`);
                            });
                        } else {
                            Swal.fire(
                                "Oops!",
                                "Something went wrong. Please try again!",
                                "error"
                            );
                        }
                    }
                });
            });



            /* ---------------- SIGNUP ---------------- */
            let otpTime = 120; // ⏱ 2 minutes
            let otpInterval = null;
            let resendLocked = false;

            /* ================= LOADER ================= */
            // function showLoader() {
            //     $(".loader").fadeIn();
            // }

            // function hideLoader() {
            //     $(".loader").fadeOut();
            // }

            /* ================= OTP TIMER ================= */
            function startOtpTimer() {

                clearInterval(otpInterval); // safety
                otpTime = 120;

                $("#otpTimer").text("02:00");
                $("#verifyOtpBtn").show();
                $("#resendOtpBtn").addClass("d-none").prop("disabled", true);
                resendLocked = true;

                otpInterval = setInterval(() => {
                    otpTime--;

                    let min = Math.floor(otpTime / 60);
                    let sec = otpTime % 60;

                    $("#otpTimer").text(
                        `${min < 10 ? '0' + min : min}:${sec < 10 ? '0' + sec : sec}`
                    );

                    if (otpTime <= 0) {
                        clearInterval(otpInterval);
                        $("#otpTimer").text("00:00");

                        $("#verifyOtpBtn").hide();
                        $("#resendOtpBtn")
                            .removeClass("d-none")
                            .prop("disabled", false);

                        resendLocked = false; // 🔓 allow resend
                    }
                }, 1000);
            }

            /* ================= VERIFY ACCOUNT (SEND OTP) ================= */
            // $("#sendOtpBtn").click(function() {

            //     if ($(this).prop("disabled")) return;

            //     // $(".text-danger").remove();
            //     $("#signupForm .text-danger").remove();
            //     $("#loginForm .text-danger").remove();
            //     showLoader();

            //     $.ajax({
            //         url: "{{ route('website.signup') }}",
            //         method: "POST",
            //         data: $("#signupForm").serialize(),

            //         success: function(res) {
            //             hideLoader();

            //             if (res.status) {
            //                 $("#signupArea").hide();
            //                 $("#otpArea").fadeIn();

            //                 $("#otpEmail").val($("#signupEmail").val());

            //                 startOtpTimer();
            //             }
            //         },

            //         error: function(xhr) {
            //             hideLoader();

            //             if (xhr.status === 422) {
            //                 let errors = xhr.responseJSON.errors;
            //                 $.each(errors, function(field, msg) {
            //                     $(`[name="${field}"]`)
            //                         .after(
            //                             `<span class="text-danger">${msg[0]}</span>`);
            //                 });
            //             }
            //         }
            //     });
            // });

            $("#sendOtpBtn").on("click", function (e) {
                e.preventDefault();

                let valid = true;
                $("#signupForm .text-danger").remove();

                function error(el, msg) {
                    el.after(`<span class="text-danger">${msg}</span>`);
                    valid = false;
                }

                const name   = $('[name="signup_name"]');
                const email  = $('[name="signup_email"]');
                const mobile = $('[name="signup_mobile_number"]');
                const pass   = $('[name="signup_password"]');
                const gst = $('[name="signup_gst"]');

                // FULL NAME
                if (!name.val()) {
                    error(name, "Full name is required");
                } else if (!nameRegex.test(name.val())) {
                    error(name, "Only letters allowed (e.g. Vivek S Patil)");
                }

                // EMAIL
                if (!email.val()) {
                    error(email, "Email is required");
                } else if (!emailRegex.test(email.val())) {
                    error(email, "Use valid email like gmail/yahoo (.co, .com)");
                }

                // MOBILE
                if (!mobile.val()) {
                    error(mobile, "Mobile number is required");
                } else if (!mobileRegex.test(mobile.val())) {
                    error(mobile, "10 digits only & must start with 6, 7, 8 or 9");
                }

                // PASSWORD
                if (!pass.val()) {
                    error(pass, "Password is required");
                } else if (pass.val().length < 6) {
                    error(pass, "Password must be minimum 6 characters");
                }

                if (gst.val() && !gstRegex.test(gst.val())) {
                    error(gst, "Enter valid GST number (e.g. 27ABCDE1234F1Z5)");
                }

                if (!valid) return; // STOP HERE

                // ✅ VALID → AJAX CALL
                showLoader();

                $.ajax({
                    url: "{{ route('website.signup') }}",
                    method: "POST",
                    data: $("#signupForm").serialize(),

                    success: function (res) {
                        hideLoader();
                        if (res.status) {
                            $("#signupArea").hide();
                            $("#otpArea").fadeIn();
                            $("#otpEmail").val($("#signupEmail").val());
                            startOtpTimer();
                        }
                    },

                    error: function (xhr) {
                        hideLoader();
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, msg) {
                                $(`[name="${field}"]`)
                                    .after(`<span class="text-danger">${msg[0]}</span>`);
                            });
                        }
                    }
                });
            });

            /* ================= VERIFY OTP ================= */
            $("#verifyOtpBtn").click(function() {

                $("#otpError").text(""); // clear previous error
                showLoader();

                $.ajax({
                    url: "{{ route('website.verify.otp') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        email: $("#otpEmail").val(),
                        otp: $("#otpInput").val()
                    },

                    success: function(res) {
                        hideLoader();

                        if (res.status) {
                            Swal.fire(
                                "Success",
                                "Registration successful!",
                                "success"
                            ).then(() => location.reload());
                        } else {
                            hideLoader();
                            //  THIS WILL SHOW BELOW OTP INPUT
                            $("#otpError").text(res.message);
                        }
                    }
                });
            });

            /* ================= RESEND OTP ================= */
            $("#resendOtpBtn").click(function() {

                if (resendLocked) return; // ❌ block multiple clicks

                resendLocked = true;
                $(this).prop("disabled", true);
                showLoader();

                $.ajax({
                    url: "{{ route('website.resend.otp') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        email: $("#otpEmail").val() // editable email
                    },

                    success: function() {
                        hideLoader();
                        startOtpTimer(); // 🔁 restart 2 min
                    }
                });
            });

        });
    </script>
