<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session ended</title>

    {{-- Standalone on purpose. This renders inside the home page's hero frame,
         so it must not pull in the site layout — that is exactly the bug it
         exists to fix, the whole website appearing inside a small panel. --}}
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            background: #FFFFFF;
            font-family: "Montserrat", system-ui, -apple-system, sans-serif;
            text-align: center;
            color: #0F172A;
        }

        .eb-icon {
            display: grid;
            place-items: center;
            width: 56px;
            height: 56px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: rgba(249, 115, 22, .12);
            color: #F97316;
            font-size: 26px;
            line-height: 1;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 18px;
            font-weight: 800;
        }

        p {
            max-width: 34ch;
            margin: 0 auto 20px;
            font-size: 14px;
            line-height: 1.6;
            color: rgba(15, 23, 42, .62);
        }

        a {
            display: inline-block;
            padding: 11px 22px;
            border-radius: 10px;
            background: #F97316;
            color: #FFFFFF;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div>
        <div class="eb-icon" aria-hidden="true">&#9201;</div>
        <h1>{{ $message }}</h1>

        @if ($reason === 'preview_expired')
            <p>Log in or register on the page around this panel to carry on exploring the map.</p>
            {{-- target="_top" so the link breaks out of the frame instead of
                 loading the site inside it. --}}
            <a href="{{ url('/') }}" target="_top">Login / Register</a>
        @else
            <p>Tell our team what you are looking for and we will put a plan together.</p>
            <a href="{{ route('website.requirement.create', ['from' => 'expired']) }}" target="_top">
                Share Your Requirement
            </a>
        @endif
    </div>
</body>

</html>
