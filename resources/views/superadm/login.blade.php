<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('asset/campaign/images/logo.png') }}">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <title>Brand Image – Admin Login</title>

    <style>
        html,
        body {
            height: 100%;
        }

        /* Left column: full-height image */
        .col-img {
            position: relative;
            overflow: hidden;
            padding: 0;
        }

        .col-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .col-img .overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,
                    rgba(0, 0, 0, 0.40) 0%,
                    rgba(0, 0, 0, 0.15) 100%);
        }

        /* Right column */
        .col-form {
            background: linear-gradient(160deg, #eef2f7 0%, #f3f0ff 60%, #fdfbff 100%);
            padding: 0;
        }

        /* Card */
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 42px 38px;
            box-shadow:
                0 20px 50px rgba(0, 0, 0, 0.09),
                0 4px 16px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 420px;
        }

        .login-logo img {
            max-width: 200px;
            height: auto;
        }

        .login-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: #344767;
            letter-spacing: 0.3px;
        }

        /* Inputs */
        .form-label {
            font-weight: 600;
            font-size: 0.875rem;
            color: #344767;
            margin-bottom: 5px;
        }

        .form-control {
            border: 1.5px solid #d2d6da;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            color: #344767;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            border-color: #17c1e8;
            box-shadow: 0 0 0 3px rgba(23, 193, 232, 0.18);
        }

        /* Password toggle */
        .input-group .form-control {
            border-right: none;
            border-radius: 8px 0 0 8px;
        }

        .input-group .btn-toggle {
            border: 1.5px solid #d2d6da;
            border-left: none;
            border-radius: 0 8px 8px 0;
            background: #fff;
            color: #9ba4b4;
            padding: 0 14px;
            transition: color .2s;
        }

        .input-group .btn-toggle:hover {
            color: #344767;
        }

        .input-group:focus-within .form-control,
        .input-group:focus-within .btn-toggle {
            border-color: #17c1e8;
        }

        .input-group:focus-within .btn-toggle {
            box-shadow: 3px 0 0 3px rgba(23, 193, 232, 0.18) inset;
        }

        /* Login button */
        .btn-login {
            background: linear-gradient(135deg, #17c1e8 0%, #0ea5c8 100%);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 11px;
            letter-spacing: 0.4px;
            box-shadow: 0 4px 14px rgba(23, 193, 232, 0.35);
            transition: background .2s, box-shadow .2s, transform .1s;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #0ea5c8 0%, #0c8fab 100%);
            box-shadow: 0 6px 18px rgba(23, 193, 232, 0.45);
            transform: translateY(-1px);
            color: #fff;
        }

        .btn-login:active {
            transform: translateY(0);
        }
    </style>
</head>

<body>

    {{-- Bootstrap 5 Grid: container-fluid > row.g-0.vh-100 > col-7 image | col-5 form --}}
    <div class="container-fluid h-100">
        <div class="row g-0 h-100">

            {{-- Left: Image Panel (hidden on mobile) --}}
            <div class="col-md-7 col-img d-none d-md-block">
                <img src="{{ asset('asset/campaign/images/loginbg.jpeg') }}" alt="Brand Image">
                <div class="overlay"></div>
            </div>

            {{-- Right: Form Panel --}}
            <div class="col-12 col-md-5 col-form d-flex align-items-center justify-content-center p-4">
                <div class="login-card">

                    {{-- Logo --}}
                    <div class="login-logo text-center mb-3">
                        <img src="{{ asset('asset/campaign/images/logo.png') }}" alt="Brand Image Logo">
                    </div>

                    {{-- Title --}}
                    <h2 class="login-title text-center mb-4">Admin Login</h2>

                    {{-- Error Alert --}}
                    @if (session('error'))
                        <div class="alert alert-danger py-2 px-3 mb-3" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" id="loginform" action="{{ route('superlogin') }}">
                        @csrf

                        {{-- Email --}}
                        <div class="mb-3">
                            <label class="form-label" for="superemail">Email Id</label>
                            <input type="email" id="superemail" name="superemail"
                                class="form-control @error('superemail') is-invalid @enderror"
                                placeholder="Enter your email">
                            @error('superemail')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="mb-3">
                            <label class="form-label" for="superpassword">Password</label>
                            <div class="input-group">
                                <input type="password" id="superpassword" name="superpassword"
                                    class="form-control @error('superpassword') is-invalid @enderror"
                                    placeholder="Enter your password">
                                <button type="button" class="btn-toggle" id="togglePassword" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('superpassword')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- reCAPTCHA --}}
                        <div class="mb-3">
                            <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>
                            @error('g-recaptcha-response')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Submit --}}
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-login">Login</button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pwd = document.getElementById('superpassword');
            const icon = this.querySelector('i');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    </script>

</body>

</html>
