<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        body{
            margin:0;
            padding:0;
            background:#fff;
        }
    </style>
    @yield('styles')
</head>
<body>
    @yield('content')
</body>
</html>
