<style>
    .otp-box-wrapper {
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .otp-box {
        width: 45px;
        height: 50px;
        text-align: center;
        font-size: 22px;
        border: 1px solid #E5E7EB;
        border-radius: 6px;
    }

    .otp-box:focus {
        border-color: #0F172A;
        outline: none;
    }

    .user-btn {
        width: 55px;
        height: 46px;
        padding: 0;
        background: #F97316;
        border-radius: 6px;
        justify-content: center;
        gap: 5px;
    }

    .user-avatar {
        width: 22px;
        height: 22px;
        object-fit: cover;
        border-radius: 50%;
    }

    .dropdown-arrow {
        font-size: 12px;
        margin-right: 2px;
    }

    {{-- The user dropdown is styled in website_css/style.css. It used to be
         duplicated here as well, with !important on nearly every line, and the
         two copies fought: the inline set won and reinstated the old fixed
         widths and margins. One source only. --}}
</style>

<!--PreLoader-->
<div class="loader">
    <div class="loader-inner">
        <div class="circle"></div>
    </div>
</div>
<!--PreLoader Ends-->

<!-- header -->
<div class="top-header-area" id="sticker">
    <div class="container mob-padding">
        <div class="row">
            <div class="col-lg-12 col-sm-12 text-center">
                <div class="main-menu-wrap">
                    <!-- logo -->
                    <div class="site-logo">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('assets/img/logo/brand_adda.webp') }}" alt="Brand Adda">
                        </a>
                    </div>
                    <!-- logo -->

                    <!-- menu start -->
                    <nav class="main-menu">
                        <ul>
                            <li class="{{ request()->is('/') ? 'active' : '' }}"><a href="{{ url('/') }}">Home</a>
                            </li>
                            {{-- Hidden while the visitor is on a shared shortlist: the
                                 Map route sends them back anyway, so offering it would
                                 just be a link that bounces. --}}
                            @unless (session()->has('shared_link_token') && !site_admin())
                                <li class="{{ request()->routeIs('website.explore') ? 'active' : '' }}"><a
                                        href="{{ route('website.explore') }}">Map</a></li>
                            @endunless
                            <li class="{{ request()->routeIs('website.about') ? 'active' : '' }}"><a
                                    href="{{ route('website.about') }}">About Us</a></li>
                            <li class="{{ request()->is('contact-us') ? 'active' : '' }}"><a
                                    href="{{ url('/contact-us') }}">Contact Us</a></li>
                        </ul>
                    </nav>

                    <div class="header-icons new-header-icons">
                        {{-- Admin mode. Signing in through "Login via Admin" leaves no
                             other trace on the public site — the customer avatar is
                             tied to the website guard, not to this — so without a
                             marker a team member cannot tell whether the Select ticks
                             are missing because they are logged out or because this
                             page does not carry them. It is also the only way back
                             out that does not take a customer session down with it. --}}
                        @if (site_admin())
                            {{-- Same markup and the same .user-menu-v2 rules as the
                                 customer account menu beside it, rather than plain
                                 Bootstrap .dropdown-item: several theme rules reach
                                 header links through the surrounding header, and a row
                                 that does not declare its own background takes whichever
                                 lands last — which turned every row into an orange block
                                 with the label lost inside it. Those rules already win
                                 that fight; .admin-menu-v2 only re-colours what should
                                 read as staff rather than customer. --}}
                            <div class="dropdown user-dropdown admin-mode-wrap">

                                <button class="btn admin-mode-pill" data-bs-toggle="dropdown"
                                    aria-label="Admin mode menu" title="Signed in as Brand Adda staff">
                                    <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                                    <span class="admin-mode-label">Admin</span>
                                    <i class="bi bi-caret-down-fill admin-mode-caret" aria-hidden="true"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end user-menu-v2 admin-menu-v2">

                                    <li class="user-info">
                                        <span class="user-info-avatar admin-info-avatar" aria-hidden="true">
                                            <i class="bi bi-shield-lock-fill"></i>
                                        </span>
                                        <div class="user-info-text">
                                            <strong>{{ site_admin('name') }}</strong>
                                            {{-- Truncated rather than wrapped, so one long
                                                 address cannot widen the card; the full one
                                                 is on the title. --}}
                                            <span title="{{ site_admin('email') }}">{{ site_admin('email') }}</span>
                                        </div>
                                    </li>

                                    <li>
                                        <a href="{{ route('dashboard') }}" class="menu-btn active">
                                            <i class="bi bi-speedometer2" aria-hidden="true"></i>
                                            Admin Panel
                                        </a>
                                    </li>

                                    <li>
                                        <a href="{{ route('website.search.view') }}" class="menu-btn">
                                            <i class="bi bi-link-45deg" aria-hidden="true"></i>
                                            Shortlist &amp; Share
                                        </a>
                                    </li>

                                    <li>
                                        <hr class="menu-sep">
                                    </li>

                                    <li>
                                        <a href="{{ route('website.admin.logout') }}" class="menu-btn logout">
                                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                            Exit admin mode
                                        </a>
                                    </li>

                                </ul>
                            </div>
                        @endif

                        @auth('website')
                            <a href="{{ route('cart.index') }}" class="btn cart-page new-btn-light position-relative">
                                <i class="bi bi-cart3 "></i>
                                @if ($cartCount > 0)
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill cart-count">
                                        {{ $cartCount }}
                                    </span>
                                @endif
                            </a>
                        @else
                            <button class="btn new-btn-light position-relative" onclick="openLoginForCart()">
                                <i class="bi bi-cart3 "></i>
                                @unless ($cartCount == 0)
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill cart-count">
                                        0
                                    </span>
                                @endunless
                            </button>
                        @endauth
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

                                <!-- USER MENU -->
                                <ul class="dropdown-menu dropdown-menu-end user-menu-v2">

                                    <!-- USER INFO -->
                                    <li class="user-info">
                                        <img src="{{ asset('asset/images/website/user.png') }}"
                                            class="user-info-avatar" alt="">
                                        <div class="user-info-text">
                                            <strong>{{ Auth::guard('website')->user()->name }}</strong>
                                            {{-- The full address is on the title, since a long one is
                                                 truncated rather than allowed to wrap the card. --}}
                                            <span title="{{ Auth::guard('website')->user()->email }}">
                                                {{ Auth::guard('website')->user()->email }}
                                            </span>
                                        </div>
                                    </li>

                                    <!-- ACTIONS -->
                                    <li>
                                        <a href="{{ route('dashboard.home') }}" class="menu-btn active">
                                            <i class="bi bi-speedometer2" aria-hidden="true"></i>
                                            Dashboard
                                        </a>
                                    </li>

                                    <li>
                                        <a href="{{ route('website.profile.view') }}" class="menu-btn">
                                            <i class="bi bi-person" aria-hidden="true"></i>
                                            Profile
                                        </a>
                                    </li>

                                    <li>
                                        <hr class="menu-sep">
                                    </li>

                                    <li>
                                        <a href="{{ route('website.logout') }}" class="menu-btn logout">
                                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
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
                        <!-- menu end -->
                    </div>
                    <div class="mobile-menu"></div>
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
                        <div class="col-md-12 p-4">
                            <!-- LOGIN FORM -->
                            <div id="loginArea">

                                <h4 class="auth-title">Login to Continue</h4>

                                <form method="POST" id="loginForm" novalidate>
                                    @csrf

                                    <div class="mb-3">
                                        <label>Email Address *</label>
                                        <input type="email" name="login_email" class="form-control" placeholder="Enter your email address">
                                    </div>

                                    <div class="mb-3">
                                        <label>Password *</label>
                                        <div class="password-wrapper">
                                            <input type="password" name="login_password"
                                                class="form-control password-field" placeholder="Enter your password">
                                            <i class="bi bi-eye-slash password-toggle"></i>
                                        </div>
                                    </div>

                                    <!-- Google reCAPTCHA -->
                                    <div class="col-md-12 mt-3">
                                        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site') }}"></div>

                                        @error('g-recaptcha-response')
                                            <span class="text-danger" style="font-size:14px;">
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-dark w-100 mt-3">Login</button>

                                    <div class="social-login mt-4">
                                        <a href="{{ route('auth.google.redirect') }}" class="google-btn">
                                            <img src="https://developers.google.com/identity/images/g-logo.png"
                                                alt="Google">
                                            Continue with Google
                                        </a>
                                    </div>

                                    <div class="auth-switch mt-3">
                                        Don't have an account?
                                        <a onclick="showSignup()">Sign Up</a>
                                    </div>

                                    {{-- Brand Adda staff. Separated by a rule and worded
                                         plainly rather than hidden: a customer who clicks
                                         it finds a box their credentials do not open. --}}
                                    <div class="auth-staff-switch">
                                        <a onclick="showAdminLogin()">
                                            <i class="bi bi-shield-lock" aria-hidden="true"></i>
                                            Login via Admin
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <!-- ADMIN / TEAM LOGIN FORM -->
                            {{-- The same credentials as the admin panel, entered here so a
                                 team member can shortlist and share hoardings from /search
                                 without a detour through /login and the dashboard. The
                                 route behind it verifies the captcha server-side and is
                                 rate limited; see Superadm\LoginController. --}}
                            <div id="adminLoginArea" style="display:none;">

                                <h4 class="auth-title">Team Login</h4>
                                <p class="auth-note">
                                    Use your Brand Adda admin panel credentials. This signs you in
                                    with full admin access on this browser.
                                </p>

                                <form method="POST" id="adminLoginForm" novalidate>
                                    @csrf

                                    <div class="mb-3">
                                        <label>Admin Email *</label>
                                        <input type="email" name="admin_email" class="form-control"
                                            placeholder="Enter your admin email" autocomplete="username">
                                    </div>

                                    <div class="mb-3">
                                        <label>Password *</label>
                                        <div class="password-wrapper">
                                            <input type="password" name="admin_password"
                                                class="form-control password-field"
                                                placeholder="Enter your password" autocomplete="current-password">
                                            <i class="bi bi-eye-slash password-toggle"></i>
                                        </div>
                                    </div>

                                    {{-- Not .g-recaptcha: that class is auto-rendered by the
                                         API on load, which hands back no widget id, and with
                                         two widgets on the page grecaptcha.getResponse() would
                                         keep answering for the first one. Rendered explicitly
                                         below so this form can read its own. --}}
                                    <div class="col-md-12 mt-3">
                                        <div id="adminCaptchaBox"></div>
                                    </div>

                                    <button type="submit" class="btn btn-dark w-100 mt-3">
                                        Login as Admin
                                    </button>

                                    <div class="auth-switch mt-3">
                                        Not staff?
                                        <a onclick="showLogin()">Customer login</a>
                                    </div>
                                </form>
                            </div>

                            <!-- SIGNUP FORM -->
                            <div id="signupArea" style="display:none;">

                                <h4 class="auth-title">Create Your Account</h4>
                                {{-- One field per row. .auth-grid is what gives the submit
                                     and the footer line their explicit full-width span. --}}
                                <form id="signupForm" class="auth-grid" novalidate>
                                    @csrf

                                    <div class="mb-3">
                                        <label>Full Name <span class="text-danger req-star">*</span></label>
                                        <input type="text" name="signup_name" class="form-control" placeholder="Enter your full name">
                                    </div>

                                    <div class="mb-3">
                                        <label>Email Id <span class="text-danger req-star">*</span></label>
                                        <input type="email" name="signup_email" id="signupEmail" class="form-control" placeholder="Enter your email address">
                                    </div>

                                    <div class="mb-3">
                                        <label>Mobile Number <span class="text-danger req-star">*</span></label>
                                        <input type="text" name="signup_mobile_number" class="form-control"
                                            maxlength="10" inputmode="numeric" autocomplete="off" placeholder="10-digit mobile number">
                                    </div>

                                    <div class="mb-3">
                                        <label>City <span class="text-danger req-star">*</span></label>
                                        <input type="text" name="signup_city" class="form-control" maxlength="120"
                                            placeholder="e.g. Nashik">
                                    </div>

                                    <div class="mb-3">
                                        <label>Company Name (optional)</label>
                                        <input type="text" name="signup_organisation" class="form-control" placeholder="Company or agency name">
                                    </div>

                                    <div class="mb-3">
                                        <label>User Type <span class="text-danger req-star">*</span></label>
                                        <select name="signup_user_type" class="form-select">
                                            <option value="">Select user type</option>
                                            @foreach (\App\Models\WebsiteUser::USER_TYPES as $type)
                                                <option value="{{ $type }}">{{ $type }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label>GST (optional)</label>
                                        <input type="text" name="signup_gst" maxlength="15" class="form-control" placeholder="15-character GST number">
                                    </div>

                                    <div class="mb-3">
                                        <label>Password <span class="text-danger req-star">*</span></label>
                                        <div class="password-wrapper">
                                            <input type="password" name="signup_password"
                                                class="form-control password-field" placeholder="Create a password">
                                            <i class="bi bi-eye-slash password-toggle"></i>
                                        </div>
                                    </div>

                                    <!-- VERIFY ACCOUNT BUTTON -->
                                    {{-- auth-grid-full: spans both columns. --}}
                                    <button type="button" class="btn btn-dark w-100 auth-grid-full" id="sendOtpBtn">
                                        Verify Account
                                    </button>

                                    <div class="auth-switch mt-3 auth-grid-full">
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

                                {{-- <div class="mb-2">
                                    <label>Enter OTP</label>
                                    <input type="text" id="otpInput" class="form-control">
                                </div> --}}

                                <div class="mb-2">
                                    <label>Enter OTP <span class="text-danger req-star">*</span></label>

                                    <div class="otp-box-wrapper">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                                    </div>

                                    <!-- hidden input to send combined OTP -->
                                    <input type="hidden" id="otpInput">
                                </div>

                                <small class="text-muted">
                                    OTP expires in <span id="otpTimer">01:00</span>
                                </small>

                                <div class="text-danger mt-2" id="otpError"></div>

                                <div class="alert alert-success py-2 d-none" id="otpSuccessMsg">
                                    New OTP has been sent successfully.
                                </div>

                                <button class="btn btn-dark w-100 mt-3" id="verifyOtpBtn" disabled>
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

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        function showSignup() {
            document.getElementById('loginArea').style.display = 'none';
            document.getElementById('adminLoginArea').style.display = 'none';
            document.getElementById('signupArea').style.display = 'block';
        }

        function showLogin() {
            document.getElementById('signupArea').style.display = 'none';
            document.getElementById('adminLoginArea').style.display = 'none';
            document.getElementById('loginArea').style.display = 'block';
        }

        // The admin captcha is rendered explicitly, and only once: reCAPTCHA
        // refuses to render into a container it already owns, and the pane is
        // shown and hidden as often as the visitor likes.
        var biAdminCaptchaId = null;

        function showAdminLogin() {
            document.getElementById('loginArea').style.display = 'none';
            document.getElementById('signupArea').style.display = 'none';
            document.getElementById('adminLoginArea').style.display = 'block';

            if (biAdminCaptchaId !== null) return;

            // api.js is loaded async, so it may not be here yet on a fast click.
            (function renderWhenReady(attempt) {
                if (window.grecaptcha && typeof grecaptcha.render === 'function') {
                    biAdminCaptchaId = grecaptcha.render('adminCaptchaBox', {
                        sitekey: "{{ config('services.recaptcha.site') }}"
                    });
                } else if (attempt < 40) {
                    setTimeout(function () { renderWhenReady(attempt + 1); }, 150);
                }
            })(0);
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

            const nameRegex = /^[A-Za-z\s]{2,50}$/;
            const mobileRegex = /^[6-9][0-9]{9}$/;
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            const gstRegex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;

            $('input[name="signup_name"]').on('input', function() {
                this.value = this.value.replace(/[^A-Za-z\s]/g, '');
            });

            $('input[name="signup_mobile_number"]').on('input', function() {
                this.value = this.value
                    .replace(/[^0-9]/g, '') // block letters
                    .substring(0, 10); // max 10 digits
            });

            $('input[name="signup_gst"]').on('input', function() {
                this.value = this.value
                    .toUpperCase() // auto uppercase
                    .replace(/[^0-9A-Z]/g, '') // only A–Z & 0–9
                    .substring(0, 15); // GST = 15 chars
            });

            /* ---------- SWITCH TABS ---------- */

            window.showSignup = function() {
                $("#loginArea").hide();
                $("#adminLoginArea").hide();
                $("#signupArea").show();
            };

            window.showLogin = function() {
                $("#signupArea").hide();
                $("#adminLoginArea").hide();
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
            /* ================= LOGIN VALIDATION + AJAX ================= */
            $("#loginForm").on("submit", function(e) {
                e.preventDefault();

                let valid = true;
                // :not(.req-star) is load-bearing. The required-field asterisks
                // are .text-danger spans living inside these same forms, so a
                // blanket remove() deleted every one of them the first time a
                // field was validated — the form lost its asterisks and never
                // got them back until the page was reloaded.
                $("#loginForm .text-danger:not(.req-star)").remove();

                function error(el, msg) {
                    el.after(`<span class="text-danger">${msg}</span>`);
                    valid = false;
                }

                const email = $('[name="login_email"]');
                const pass = $('[name="login_password"]');

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

                // CAPTCHA (skip on localhost)
                if (window.location.hostname !== 'localhost' && grecaptcha.getResponse().length === 0) {
                    $(".g-recaptcha").after(
                        `<span class="text-danger d-block mt-1">Please verify that you are not a robot</span>`
                    );
                    valid = false;
                }

                if (!valid) return; // STOP HERE

                // AJAX only if validation passes
                showLoader();

                $.ajax({
                    url: "{{ route('website.login') }}",
                    method: "POST",
                    data: $(this).serialize(),

                    success: function(res) {
                        hideLoader();

                        if (res.status) {
                            let redirectUrl = sessionStorage.getItem('redirect_after_login');

                            Swal.fire({
                                icon: 'success',
                                title: 'Welcome!',
                                text: 'Login successful. Start adding your outdoor media now.',
                                confirmButtonText: 'Continue'
                            }).then(() => {
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

                    error: function(xhr) {
                        hideLoader();

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, msg) {
                                $(`#loginForm [name="${field}"]`)
                                    .after(
                                        `<span class="text-danger">${msg[0]}</span>`);
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

            /* ================= ADMIN / TEAM LOGIN ================= */
            $("#adminLoginForm").on("submit", function(e) {
                e.preventDefault();

                let valid = true;
                // Messages only; the asterisks stay. @see the note on #loginForm.
                $("#adminLoginForm .text-danger:not(.req-star)").remove();

                function error(el, msg) {
                    el.after(`<span class="text-danger">${msg}</span>`);
                    valid = false;
                }

                const email = $('[name="admin_email"]');
                const pass = $('[name="admin_password"]');
                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

                if (!email.val()) {
                    error(email, "Email is required");
                } else if (!emailRegex.test(email.val())) {
                    error(email, "Enter valid email address");
                }

                if (!pass.val()) {
                    error(pass, "Password is required");
                }

                // Same localhost exemption the customer login uses, so a dev
                // box with no keys configured can still sign in. The server
                // verifies for real wherever the feature is switched on, which
                // is what actually decides this.
                if (window.location.hostname !== 'localhost' &&
                    biAdminCaptchaId !== null &&
                    grecaptcha.getResponse(biAdminCaptchaId).length === 0) {
                    $("#adminCaptchaBox").after(
                        `<span class="text-danger d-block mt-1">Please verify that you are not a robot</span>`
                    );
                    valid = false;
                }

                if (!valid) return;

                showLoader();

                $.ajax({
                    url: "{{ route('website.admin.login') }}",
                    method: "POST",
                    data: $(this).serialize(),

                    success: function(res) {
                        hideLoader();

                        if (res.status) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Admin access enabled',
                                text: res.message +
                                    ' You can now shortlist hoardings and generate shareable links.',
                                confirmButtonText: 'Continue'
                            }).then(() => {
                                // Reload rather than just closing the modal: the
                                // Select ticks, the Share bar and the dropped
                                // search timer are all rendered server-side, and
                                // none of them exist on the page as it stands.
                                window.location.reload();
                            });
                        } else {
                            // The captcha is single-use — a rejected attempt
                            // must not be retried against a spent token.
                            if (biAdminCaptchaId !== null) grecaptcha.reset(biAdminCaptchaId);

                            $("#adminLoginForm").prepend(
                                `<div class="text-danger mb-2">${res.message}</div>`
                            );
                        }
                    },

                    error: function(xhr) {
                        hideLoader();
                        if (biAdminCaptchaId !== null) grecaptcha.reset(biAdminCaptchaId);

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, msg) {
                                $(`#adminLoginForm [name="${field}"]`)
                                    .after(`<span class="text-danger">${msg[0]}</span>`);
                            });
                        } else if (xhr.status === 429) {
                            // The route is throttled tighter than the customer
                            // login; say so rather than showing "went wrong".
                            Swal.fire("Too many attempts",
                                "Please wait a minute and try again.", "warning");
                        } else {
                            Swal.fire("Oops!", "Something went wrong. Please try again!", "error");
                        }
                    }
                });
            });


            /* ---------------- SIGNUP ---------------- */
            let otpTime = 120; // ⏱ 2 minutes
            let otpInterval = null;
            let resendLocked = false;

            /**
             * Hand the Resend button back to the visitor.
             *
             * The click handler locks it before the request goes out, and the
             * lock is normally lifted by the countdown reaching zero. If the
             * request itself fails there is no countdown running to do that, so
             * without this the button stays disabled until the modal is closed.
             */
            function releaseResend() {
                resendLocked = false;
                $("#resendOtpBtn").removeClass("d-none").prop("disabled", false);
            }
            /* ================= OTP TIMER ================= */
            function validateOtp() {
                const otp = $("#otpInput").val();

                if (otp.length < 6) {
                    $("#verifyOtpBtn").prop("disabled", true);
                    return false;
                }

                $("#verifyOtpBtn").prop("disabled", false);
                return true;
            }

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

            function clearOtpInputs() {
                const otpBoxes = document.querySelectorAll(".otp-box");

                otpBoxes.forEach(box => box.value = '');

                document.getElementById("otpInput").value = '';

                $("#verifyOtpBtn").prop("disabled", true);
                $("#otpError").text('');

                // focus first box
                otpBoxes[0]?.focus();
            }
            $("#sendOtpBtn").on("click", function(e) {
                e.preventDefault();

                let valid = true;
                // Messages only; the asterisks stay. @see the note on #loginForm.
                $("#signupForm .text-danger:not(.req-star)").remove();

                function error(el, msg) {
                    el.after(`<span class="text-danger">${msg}</span>`);
                    valid = false;
                }

                const name = $('[name="signup_name"]');
                const email = $('[name="signup_email"]');
                const mobile = $('[name="signup_mobile_number"]');
                const pass = $('[name="signup_password"]');
                const gst = $('[name="signup_gst"]');
                const city = $('[name="signup_city"]');
                const userType = $('[name="signup_user_type"]');

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

                // CITY — free text, so presence is the only sensible check.
                if (!city.val() || !city.val().trim()) {
                    error(city, "City is required");
                }

                // USER TYPE
                if (!userType.val()) {
                    error(userType, "Please select a user type");
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

                // VALID → AJAX CALL
                showLoader();
                submitSignup(false);
            });

            /* Fetches the session's current CSRF token and writes it back into
               the page, then runs `done`.

               @csrf stamps the token in when the page is rendered, so a page
               left open while something else in this browser logs in or out is
               holding one the session has moved past — and the only thing the
               visitor sees is a 419. Rather than making them retype the whole
               registration form, pick up the live token and go again. */
            function withFreshCsrf(done) {
                $.get("{{ route('csrf.token') }}")
                    .done(function(res) {
                        $('meta[name="csrf-token"]').attr('content', res.token);
                        $('input[name="_token"]').val(res.token);
                        done();
                    })
                    .fail(function() {
                        hideLoader();
                        Swal.fire("Session expired",
                            "Please refresh the page and try again.", "warning");
                    });
            }

            /* `retried` guards against looping: one refresh-and-resend is a
               stale token, a second 419 is something else. */
            function submitSignup(retried) {
                $.ajax({
                    url: "{{ route('website.signup') }}",
                    method: "POST",
                    data: $("#signupForm").serialize(),

                    success: function(res) {
                        hideLoader();
                        if (res.status) {
                            $("#signupArea").hide();
                            $("#otpArea").fadeIn();
                            $("#otpEmail").val($("#signupEmail").val());
                            startOtpTimer();
                        } else {
                            // The controller answers "already registered",
                            // "deleted by admin" and the like as a normal 200
                            // carrying status:false. Without this the form just
                            // sat there and the visitor was told nothing.
                            Swal.fire("Could not sign up", res.message ||
                                "Something went wrong. Please try again.", "warning");
                        }
                    },

                    error: function(xhr) {
                        // A stale CSRF token. Refresh it and resend once — the
                        // loader stays up, and the form keeps what was typed.
                        if (xhr.status === 419 && !retried) {
                            withFreshCsrf(function() {
                                submitSignup(true);
                            });
                            return;
                        }

                        hideLoader();
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, msg) {
                                $(`[name="${field}"]`)
                                    .after(
                                        `<span class="text-danger">${msg[0]}</span>`);
                            });
                        } else if (xhr.status === 419) {
                            Swal.fire("Session expired",
                                "Please refresh the page and try again.", "warning");
                        } else {
                            // Anything else — a 503 because the OTP mail could
                            // not go out, or a genuine server error. Previously
                            // the loader vanished and nothing happened at all,
                            // so the button looked simply broken.
                            Swal.fire("Could not sign up",
                                (xhr.responseJSON && xhr.responseJSON.message) ||
                                "Something went wrong. Please try again.", "error");
                        }
                    }
                });
            }

            /* ================= VERIFY OTP ================= */
            $("#verifyOtpBtn").click(function() {

                $("#otpError").text("");

                if (!validateOtp()) {
                    $("#otpError").text("OTP is required");
                    return; // STOP AJAX
                }

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
                            $("#otpError").text(res.message);
                        }
                    }
                });
            });

            function showOtpSuccessMessage(message = "New OTP has been sent successfully.") {
                const msgBox = $("#otpSuccessMsg");

                msgBox.text(message).removeClass("d-none");

                // Auto hide after 5 seconds
                setTimeout(() => {
                    msgBox.addClass("d-none");
                }, 5000);
            }

            /* ================= RESEND OTP ================= */
            $("#resendOtpBtn").click(function() {

                if (resendLocked) return; // block multiple clicks

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

                    success: function(res) {
                        hideLoader();

                        // A 200 can still carry status:false ("Email not
                        // registered"). This used to announce "New OTP has been
                        // sent" regardless, and restart the timer on an OTP that
                        // was never sent.
                        if (res && res.status === false) {
                            releaseResend();
                            $("#otpError").text(res.message ||
                                "Could not resend the code. Please try again.");
                            return;
                        }

                        clearOtpInputs(); // CLEAR OLD OTP
                        startOtpTimer(); // restart 2 min
                        showOtpSuccessMessage(); // show success msg
                    },

                    // There was no error callback here at all, so a failed
                    // resend left the global loader spinning and the button
                    // disabled for good — the visitor was stranded on the OTP
                    // screen with no way forward and nothing explaining why.
                    error: function(xhr) {
                        hideLoader();
                        releaseResend();
                        $("#otpError").text(
                            (xhr.responseJSON && xhr.responseJSON.message) ||
                            "Could not resend the code. Please try again."
                        );
                    }
                });
            });

            const otpBoxes = document.querySelectorAll(".otp-box");
            const otpHiddenInput = document.getElementById("otpInput");

            otpBoxes.forEach((box, index) => {

                // ONLY NUMBERS
                box.addEventListener("input", (e) => {
                    box.value = box.value.replace(/[^0-9]/g, '');

                    if (box.value && index < otpBoxes.length - 1) {
                        otpBoxes[index + 1].focus();
                    }

                    updateOtpValue();
                });

                // BACKSPACE MOVE
                box.addEventListener("keydown", (e) => {
                    if (e.key === "Backspace" && !box.value && index > 0) {
                        otpBoxes[index - 1].focus();
                    }
                });

                // PASTE FULL OTP
                box.addEventListener("paste", (e) => {
                    e.preventDefault();
                    const pasteData = e.clipboardData.getData("text").replace(/\D/g, '');

                    pasteData.split('').forEach((digit, i) => {
                        if (otpBoxes[i]) {
                            otpBoxes[i].value = digit;
                        }
                    });

                    updateOtpValue();
                    otpBoxes[Math.min(pasteData.length, otpBoxes.length) - 1]?.focus();
                });
            });

            // JOIN OTP INTO HIDDEN INPUT
            function updateOtpValue() {
                otpHiddenInput.value = Array.from(otpBoxes)
                    .map(input => input.value)
                    .join('');

                validateOtp(); // 🔥 enable/disable button
            }



        });
    </script>
