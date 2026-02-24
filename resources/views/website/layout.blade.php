<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- <title>@yield('title', 'Website')</title> --}}
    <title>Printing | Branding | Outdoor Advertising Agency
        Nashik | Brand Image</title>
    {{-- Bootstrap 5 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="icon" type="image/svg+xml" href="{{ asset('asset/theamoriginalalf/images/favicon.png') }}">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.css"/>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- fontawesome -->
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <!-- bootstrap -->
    {{-- <link rel="stylesheet" href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}"> --}}
    <!-- owl carousel -->
    <link rel="stylesheet" href="{{ asset('assets/css/owl.carousel.css') }}">
    <!-- magnific popup -->
    <link rel="stylesheet" href="{{ asset('assets/css/magnific-popup.css') }}">
    <!-- animate css -->
    <link rel="stylesheet" href="{{ asset('assets/css/animate.css') }}">
    <!-- mean menu css -->
    <link rel="stylesheet" href="{{ asset('assets/css/meanmenu.min.css') }}">
    <!-- main style -->
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
    <!-- responsive -->
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">

    {{-- Custom Website CSS --}}
    <link rel="stylesheet" href="{{ asset('asset/css/website_css/style.css') }}">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet"> --}}

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content')
            }
        });
    </script>

</head>
<style>
    .header-icons .btn,
.header-icons a {
    width: 55px;
    height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.header-icons {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icons a {
     /* width: 55px;
    height: 46px; */
    background: #f28123;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    position: relative;
}

.header-icons i,
.header-icons img {
    font-size: 18px;
    /* width: 22px;
    height: 22px; */
}
.cart-count {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #007bff;
    color: #fff;
    font-size: 12px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-icon img {
    width: 22px;
    height: 22px;
    object-fit: cover;
    border-radius: 50%;
}

</style>
<body class="d-flex flex-column min-vh-100 {{ auth('website')->check() ? 'user-logged-in' : 'user-guest' }}">

    {{-- Include Header --}}
    {{-- @include('website.includes.header') --}}
    @include('website.includes.newheader')

    {{-- Main Content --}}
    <main class="flex-fill">
        @yield('content')
    </main>

    {{-- Include Footer --}}
    @include('website.includes.footer')

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>

    <!-- GLOBAL LOADER -->
    <div id="globalLoader" aria-hidden="true" class="global-loader">
        <div class="global-loader__backdrop"></div>

        <div class="loader-wrapper">
            <div class="dotted-loader"></div>
            <div class="loader-text">Please wait...</div>
        </div>
    </div>

    @yield('scripts')
<script src="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.js"></script>
</body>

</html>
