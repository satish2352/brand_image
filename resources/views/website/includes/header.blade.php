<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light navbar-custom shadow-sm">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand fw-bold" href="{{ url('/') }}">
            <img src="{{ asset('asset/images/website/logo.png') }}" alt="..." style="height: 65px;">
        </a>

        <!-- Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Center Menu -->
        <div class="collapse navbar-collapse" id="navbarContent">

            <ul class="navbar-nav mb-2 mb-lg-0 text-center">
                <li class="nav-item"><a class="nav-link" href="{{ url('/') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/about') }}">About</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/contact') }}">Contact Us</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ url('/media-search') }}">Search Media</a></li>
                 <li class="nav-item"><a class="nav-link" href="{{ url('/campaign-list') }}">Campaign List</a></li>
                 
                  <li class="nav-item">
    <a class="nav-link" href="{{ route('campaign.list') }}">
        Campaign List
    </a>
</li>

  <li class="nav-item"><a class="nav-link" href="{{ url('/contact-us') }}">Contact Us</a></li>
       <li class="nav-item">
    
</li>

            </ul>

            <!-- Right Login -->
            <div class="ms-lg-auto d-flex align-items-center">

                {{-- @if(session()->has('website_user')) --}}

                <div class="ms-lg-auto d-flex align-items-center gap-3">

    {{-- ================= CART ICON ================= --}}
   @auth('website')
<a href="{{ route('cart.index') }}" class="btn btn-light position-relative">
    <i class="bi bi-cart3 fs-5"></i>
    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
        {{ $cartCount }}
    </span>
</a>
@else
<button class="btn btn-light position-relative" onclick="openLoginForCart()">
    <i class="bi bi-cart3 fs-5"></i>
    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
        0
    </span>
</button>
@endauth


    {{-- ================= USER / LOGIN ================= --}}
@auth('website')

                    <div class="dropdown user-dropdown">

                        <button class="btn d-flex align-items-center user-btn" 
                                data-bs-toggle="dropdown">

                            <!-- USER NAME + ARROW -->
                            {{-- <span class="me-2 user-name">{{ session('website')->name }}</span> --}}
                            
                            <i class="bi bi-caret-down-fill dropdown-arrow"></i>

                            <!-- USER AVATAR -->
                            <img src="{{ asset('asset/images/website/user.png') }}" 
                                class="user-avatar" alt="User">
                        </button>

                        <!-- SUPER ATTRACTIVE DROPDOWN -->
                        <ul class="dropdown-menu dropdown-menu-end user-menu shadow-lg">

                            <li class="dropdown-header text-center">
                                @auth('website')
    <strong>{{ Auth::guard('website')->user()->name }}</strong><br>
    <small class="text-muted">
        {{ Auth::guard('website')->user()->email }}
    </small>
@endauth

                                {{-- <strong>{{ session('website')->name }}</strong><br>
                                <small class="text-muted">{{ session('website')->email }}</small> --}}
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item" href="{{ url('/dashboard') }}">
                                    <i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item text-danger" href="{{ route('website.logout') }}">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>

                    </div>

                @else

<button class="login-btn" data-bs-toggle="modal" data-bs-target="#authModal">
    <i class="bi bi-person-circle"></i> Login
</button>

                @endif

            </div>

        </div>

    </div>
</nav>



<!-- LOGIN / SIGNUP MODAL -->
<!-- AUTH MODAL -->
<div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="padding: 0.5rem 0.5rem;">
                <h5 class="modal-title">Account Access</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">

                    <!-- LEFT IMAGE -->
                    <div class="col-md-5 auth-modal-left">
                        <img src="/images/login-banner.png" alt="Image">
                    </div>

                    <!-- RIGHT SIDE -->
                    <div class="col-md-7 p-4">

                        <!-- LOGIN FORM -->
                        <div id="loginArea">

                            <h4 class="auth-title">Login to Continue</h4>
                            {{-- action="{{ route('website.login') }}" --}}
                            <form method="POST" id="loginForm" action="{{ route('website.login') }}">
                                @csrf

                                <div class="mb-3">
                                    <label>Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="login_email" class="form-control" value="{{ old('email') }}">
                                    @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3">
                                    <label>Password <span class="text-danger">*</span></label>
                                    {{-- <input type="password" name="login_password" class="form-control"> --}}
                                    <div class="password-wrapper">
                                        <input type="password" name="login_password" class="form-control password-field">
                                        <i class="bi bi-eye-slash password-toggle"></i>
                                    </div>
                                    @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <button type="submit" class="btn btn-dark w-100 mt-2">Login</button>

                                <div class="auth-switch">
                                    Don't have an account?
                                    <a onclick="showSignup()">Sign Up</a>
                                </div>
                            </form>
                        </div>


                        <!-- SIGN-UP FORM (Initially Hidden) -->
                        <div id="signupArea" style="display:none;">

                            <h4 class="auth-title">Create Your Account</h4>
                            {{-- action="{{ route('website.signup') }}" --}}
                            <form method="POST" id="signupForm" action="{{ route('website.signup') }}">
                                @csrf

                                <div class="mb-3">
                                    <label>Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="signup_name" class="form-control" value="{{ old('name') }}">
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3">
                                    <label>Email Id <span class="text-danger">*</span></label>
                                    <input type="email" name="signup_email" class="form-control" value="{{ old('email') }}">
                                    @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3">
                                    <label>Mobile Number <span class="text-danger">*</span></label>
                                    <input type="text" name="signup_mobile_number" class="form-control" value="{{ old('mobile_number') }}">
                                    @error('mobile_number') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3">
                                    <label>Organisation <span class="text-danger">*</span></label>
                                    <input type="text" name="signup_organisation" class="form-control">
                                    @error('organisation') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3">
                                    <label>GST <small>(optional)</small></label>
                                    <input type="text" name="signup_gst" class="form-control">
                                    @error('gst') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3">
                                    <label>Password <span class="text-danger">*</span></label>
                                    {{-- <input type="password" name="signup_password" class="form-control"> --}}
                                    <div class="password-wrapper">
                                        <input type="password" name="signup_password" class="form-control password-field">
                                        <i class="bi bi-eye-slash password-toggle"></i>
                                    </div>
                                    @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <button type="submit" class="btn btn-dark w-100 mt-2">Sign Up</button>

                                <div class="auth-switch">
                                    Already have an account?
                                    <a onclick="showLogin()">Login</a>
                                </div>
                            </form>
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

    $(document).on("click", ".password-toggle", function () {
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
$(document).ready(function () {

    /* ---------- SWITCH TABS ---------- */

    window.showSignup = function () {
        $("#loginArea").hide();
        $("#signupArea").show();
    };

    window.showLogin = function () {
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
    $("#loginForm").on("submit", function(e) {
        e.preventDefault();

        $(".text-danger").remove();
        showLoader();

        $.ajax({
            url: "{{ route('website.login') }}",
            method: "POST",
            data: $(this).serialize(),

            success: function(res){
                hideLoader();

                // if(res.status){
                //     Swal.fire("Success!", res.message, "success")
                //     .then(() => window.location.reload());
                // } 
                if (res.status) {

                        let redirectUrl = sessionStorage.getItem('redirect_after_login');

                        Swal.fire("Success!", res.message, "success").then(() => {

                            if (redirectUrl) {
                                sessionStorage.removeItem('redirect_after_login');
                                window.location.href = redirectUrl; //  ADD TO CART
                            } else {
                                window.location.reload(); // normal login
                            }

                        });
                    }

                else {
                    Swal.fire("Error!", res.message, "error");
                }
            },

            error: function(xhr){
                hideLoader();

                // If validation error (422)
                if (xhr.status === 422) {
                    $("#authModal").modal("show");
                    showLogin();

                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, msg){
                        $(`#loginForm [name="${field}"]`).after(`<span class="text-danger">${msg[0]}</span>`);
                    });

                } else {
                    // Any other server/API/database error
                    Swal.fire(
                        "Oops!",
                        "Something went wrong. Please try again!",
                        "error"
                    );
                }
            }

            // error: function(xhr){
            //     hideLoader();

            //     $("#authModal").modal("show");
            //     showLogin();

            //     let errors = xhr.responseJSON.errors;
            //     $.each(errors, function(field, msg){
            //         $(`#loginForm [name="${field}"]`).after(`<span class="text-danger">${msg[0]}</span>`);
            //     });
            // }
        });
    });


    /* ---------------- SIGNUP ---------------- */
    $("#signupForm").on("submit", function(e) {
        e.preventDefault();
        $(".text-danger").remove();
        showLoader();

        $.ajax({
            url: "{{ route('website.signup') }}",
            method: "POST",
            data: $(this).serialize(),

            success: function(res){
                hideLoader();

                Swal.fire("Registered!", res.message, "success")
                .then(() => showLogin());
            },

            error: function(xhr){
                hideLoader();

                // If validation error (422)
                if (xhr.status === 422) {
                    $("#authModal").modal("show");
                    showSignup();

                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, msg){
                        $(`#signupForm [name="${field}"]`).after(`<span class="text-danger">${msg[0]}</span>`);
                    });

                } else {
                    // Any other server/API/database error
                    Swal.fire(
                        "Oops!",
                        "Something went wrong. Please try again!",
                        "error"
                    );
                }
            }
            // error: function(xhr){
            //     hideLoader();

            //     $("#authModal").modal("show");
            //     showSignup();

            //     let errors = xhr.responseJSON.errors;
            //     $.each(errors, function(field, msg){
            //         $(`#signupForm [name="${field}"]`).after(`<span class="text-danger">${msg[0]}</span>`);
            //     });
            // }
        });
    });

});

</script>


