<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('asset/campaign/images/logo.png') }}"> -->
    <link rel="icon" type="image/webp" href="{{ asset('assets/img/logo/brand_adda_browser.webp') }}">
    <link rel="shortcut icon" type="image/webp" href="{{ asset('assets/img/logo/brand_adda_browser.webp') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/logo/brand_adda_browser.webp') }}">
    <title>Brand Adda Maharashtra's Outdoor Media Platform</title>
    <link href="{{ asset('asset/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


    <link href="{{ asset('asset/css/colors/blue.css') }}" id="theme" rel="stylesheet">
    <link href="{{ asset('asset/css/satish.css') }}" id="theme" rel="stylesheet">
    <script src="{{ asset('asset/plugins/jquery/jquery-3.6.0.min.js') }}"></script>

    <style>
        .scroll-sidebar {
            overflow: auto !important;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
            height: 100dvh !important;
        }

        .scroll-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .scroll-sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .scroll-sidebar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .scroll-sidebar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>

<body class="fix-header fix-sidebar card-no-border">
    @php
        // Step 1: get admin ID saved in session
        $adminId = session('user_id') ?? session('id');

        // Step 2: load user object from DB if available
        $admin = $adminId ? \App\Models\User::find($adminId) : null;

        // Step 3: unread count
        $notifyCount = \App\Models\Notification::where('user_id', $adminId)->where('is_read', 0)->count()
            // Vendor Inventory module notifications share this bell.
            + \App\Models\PanelNotification::forAdmin()->where('is_read', false)->count();

    @endphp



    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2"
                stroke-miterlimit="10" />
        </svg>
    </div>
    <div id="main-wrapper">
        <header class="topbar">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <div class="navbar-header">
                    <a class="navbar-brand" href="">


                        <img src="{{ asset('assets/img/logo/brand_adda.webp') }}" class="light-logo" alt="Brand Adda"
                            style="height: 50px; marign:10px !important;" />
                    </a>
                </div>
                <div class="navbar-collapse">
                    <ul class="navbar-nav mr-auto mt-md-0">
                        <li class="nav-item"> <a
                                class="nav-link nav-toggler d-block d-md-none text-muted waves-effect waves-dark"
                                href="javascript:void(0)"><i class="mdi mdi-menu"></i></a> </li>
                        <li class="nav-item"> <a
                                class="nav-link sidebartoggler d-none d-md-block text-muted waves-effect waves-dark"
                                href="javascript:void(0)"><i class="ti-menu"></i></a> </li>


                    </ul>
                    <ul class="navbar-nav my-lg-0 d-flex justify-content-center align-items-center">
                        <?php
                        //  dump(session()->all());
                        ?>
                        <li class="nav-item">
                            <a class="nav-link text-muted waves-effect waves-dark" href="javascript:void(0)"
                                data-toggle="modal" data-target="#notificationModal">

                                <i class="fa fa-bell"></i>

                                @if ($notifyCount > 0)
                                    <span class="badge badge-danger" style="position:absolute; top:5px; right:5px;">
                                        {{ $notifyCount }}
                                    </span>
                                @endif
                            </a>

                        </li>

                        <li class="fnt-size">{{ session('name') }}</li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark" href="#"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img
                                    src="{{ asset('asset/images/users/1.jpg') }}" alt="user"
                                    class="profile-pic" /></a>
                            <div class="dropdown-menu dropdown-menu-right scale-up">
                                <ul class="dropdown-user">
                                    <li>
                                        <a href="{{ route('admin.change-password') }}"><i class="fa fa-key"></i> Change
                                            Password</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.logout') }}"><i class="fa fa-power-off"></i>
                                            Logout</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark" href="#"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i></i></a>
                            <div class="dropdown-menu dropdown-menu-right scale-up"> <a class="dropdown-item"
                                    href="#"><i class="flag-icon flag-icon-in"></i> India</a> <a
                                    class="dropdown-item" href="#"><i class="flag-icon flag-icon-fr"></i>
                                    French</a> <a class="dropdown-item" href="#"><i
                                        class="flag-icon flag-icon-cn"></i> China</a> <a class="dropdown-item"
                                    href="#"><i class="flag-icon flag-icon-de"></i> Dutch</a> </div>
                        </li>

                    </ul>
                </div>
            </nav>
        </header>
        <aside class="left-sidebar">
            <div class="scroll-sidebar">
                <div class="user-profile" style="margin-top: 28px;"></div>
                @include('superadm.layout.super-menu')
            </div>
        </aside>
        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row page-titles">
                    <div class="col-md-5 col-12 align-self-right ">
                    </div>
                </div>
                <div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header" style="align-items:center">
                                <h5 class="modal-title">Notifications</h5>
                                <a href="javascript:void(0)" id="markAllNotificationsRead" style="margin-left:auto;margin-right:12px;font-size:13px;font-weight:600;color:#00929c;white-space:nowrap">
                                    <i class="mdi mdi-check-all"></i> Mark all as read</a>

                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-left:0">
                                    <span aria-hidden="true">&times;</span>
                                </button>


                            </div>
                            <div class="modal-body" id="notificationList">
                                Loading...
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    // Bell count: set it to a number (0 removes the badge).
                    function setNotificationCount(count) {
                        let badge = $('.fa-bell').next('span.badge');
                        if (count > 0) {
                            if (badge.length) {
                                badge.text(count);
                            } else {
                                $('.fa-bell').after('<span class="badge badge-danger" style="position:absolute; top:5px; right:5px;">' + count + '</span>');
                            }
                        } else {
                            badge.remove();
                        }
                    }

                    function refreshNotificationCount() {
                        $.get("{{ route('admin.notifications.count') }}", function(count) {
                            let badge = $('.fa-bell').next('span.badge');

                            if (count > 0) {
                                if (badge.length) {
                                    badge.text(count);
                                } else {
                                    $('.fa-bell').after(
                                        '<span class="badge badge-danger" style="position:absolute; top:5px; right:5px;">' +
                                        count + '</span>');
                                }
                            } else {
                                badge.remove();
                            }
                        });
                    }

                    refreshNotificationCount();
                    setInterval(refreshNotificationCount, 10000);
                </script>
                <script>
                    // The modal sits inside .page-wrapper, whose stacking layer
                    // kept it underneath the backdrop; it belongs on <body>.
                    $('#notificationModal').appendTo('body');

                    // Opening the bell only lists the notifications; nothing is marked read.
                    // A notification is marked read when it is clicked (its link marks just that
                    // one and opens it), and "Mark all as read" clears them all.
                    $('#notificationModal').on('shown.bs.modal', function() {
                        $.get("{{ route('admin.notifications.data') }}", function(res) {
                            $('#notificationList').html(res);
                        });
                    });

                    // Clicking an unread one: take it off the count straight away (the link itself
                    // marks it read on the server and opens it).
                    $(document).on('click', '#notificationList a[data-unread="1"]', function() {
                        const badge = $('.fa-bell').next('span.badge');
                        if (badge.length) setNotificationCount(Math.max(0, (parseInt(badge.text(), 10) || 0) - 1));
                        $(this).attr('data-unread', '0').removeClass('bn-unread').find('.bn-new').remove();
                    });

                    $('#markAllNotificationsRead').on('click', function() {
                        $.post("{{ route('admin.notifications.markRead') }}", { _token: "{{ csrf_token() }}" }, function() {
                            setNotificationCount(0);
                            $('#notificationList .bn-unread').removeClass('bn-unread');
                            $('#notificationList .bn-new').remove();
                            $('#notificationList a[data-unread="1"]').attr('data-unread', '0');
                        });
                    });
                </script>
                @yield('content')
                @include('toast')
                @include('superadm.layout.footer')
