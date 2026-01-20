<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Coming Soon | Brand Image</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            height: 100vh;
            background:
                linear-gradient(rgba(228, 148, 72, 0.65), rgba(72, 60, 43, 0.65)),
                url("{{ asset('assets/img/coming-soon-bg.jpg') }}") center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .coming-wrapper {
            text-align: center;
            padding: 40px;
            max-width: 600px;
        }

        .logo {
            margin-bottom: 20px;
        }

        .logo img {
            max-width: 160px;
        }

        h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }

        p {
            font-size: 18px;
            color: #ddd;
            margin-bottom: 30px;
        }

        .divider {
            width: 80px;
            height: 4px;
            background: #ffc107;
            margin: 0 auto 30px;
            border-radius: 5px;
        }

        .footer-text {
            font-size: 14px;
            color: #bbb;
            margin-top: 40px;
        }

        @media (max-width: 576px) {
            h1 {
                font-size: 34px;
            }

            p {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>

    <div class="coming-wrapper">

        <!-- LOGO -->
        <div class="logo">
            <img src="{{ asset('asset/images/website/logo.png') }}" alt="Brand Image">
        </div>

        <h1>🚀 Coming Soon</h1>

        <div class="divider"></div>

        <p>
            We are working hard to launch something amazing.<br>
            Stay tuned — we’ll be live very soon!
        </p>

        <div class="footer-text">
            © {{ date('Y') }} Brand Image. All rights reserved.
        </div>

    </div>

</body>

</html>
