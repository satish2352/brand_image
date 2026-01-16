<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('asset/theamoriginalalf/images/logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <title> Brand Image </title>

    <link id="pagestyle" href="{{ asset('asset/theamoriginalalf/css/soft-ui-dashboard.css?v=1.0.3') }}"
        rel="stylesheet" />

    <!-- DataTables-->
    <style>
        .input-group input.form-control {
            padding-right: 40px;
            /* space for the icon */
        }

        .input-group-text {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
        }

        .input-group:not(.has-validation)> :not(:last-child):not(.dropdown-toggle):not(.dropdown-menu) {
            border-top-right-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }

        .login-bg {
            height: 100vh;
            overflow: hidden;
        }

        .login-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* 🔥 key line */
        }

        .login-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px 25px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            max-width: 600px;
            margin: auto;
        }

        .login-card .card-header img {
            margin-bottom: 15px;
        }

        .login-card label {
            color: #344767;
            /* dark soft-ui text */
            font-weight: 500;
        }
        .input-group .form-control:not(:last-child){
            border-right: 1px solid #d2d6da;
        }
    </style>

</head>

<body>

    <div class="container position-sticky z-index-sticky top-0">
        <div class="row">
            <div class="col-12">

            </div>
        </div>
    </div>
    <main class="main-content  mt-0">
        <section>
            <div class="page-header min-vh-75" style="padding-bottom: 40px; margin: 0px;">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-sm-6 login-bg d-none d-sm-block">
                            <img src="{{ asset('asset/theamoriginalalf/images/loginbg.jpeg') }}" alt="Login Image">
                        </div>



                        <div class="col-sm-6 d-flex flex-column mx-auto">
                            <div class="card login-card">
                                <div class="card-header pb-0 text-left bg-transparent text-center">
                                    <img src="{{ asset('asset/theamoriginalalf/images/logo.png') }}"
                                        style="width: 329px;">
                                </div>

                                <div class="card-body">

                                    @if (session('error'))
                                        <div class="alert alert-danger">{{ session('error') }}</div>
                                    @endif

                                    <form class="form-horizontal form-material" method="POST" id="loginform"
                                        action="{{ route('superlogin') }}">
                                        @csrf
                                        <label style="color:#000">User name</label>
                                        <div class="mb-3">
                                            <input type="text" id="superemail" name="superemail" value=""
                                                class="form-control" placeholder="User" aria-label="user"
                                                aria-describedby="email-addon">
                                            @error('superemail')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <label style="color:#000">Password</label>
                                        <div class="mb-3">
                                            <div class="input-group input-group-outline">
                                                <input type="password" id="superpassword" name="superpassword"
                                                    class="form-control" placeholder="Password">
                                                <span class="input-group-text" id="togglePassword"
                                                    style="cursor: pointer; background: transparent; border: none;">
                                                    <i class="fas fa-eye" style="color: #999;"></i>
                                                </span>
                                            </div>
                                            @error('superpassword')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}">
                                            </div>
                                            @error('g-recaptcha-response')
                                                <span class="text-danger"
                                                    style="font-size: 14px;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" class="btn bg-gradient-info w-100 mt-4 mb-0">Login
                                                </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </section>
    </main>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#superpassword');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // toggle the eye icon
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    </script>

</body>

</html>
