@php
    /** @var \App\Models\Vendor $vendor */
    $vendor = auth('vendor')->user();
    $vpUnread = \App\Models\PanelNotification::forVendor($vendor->id)->where('is_read', false)->count();
    $vpNewEnquiries = \App\Models\VendorEnquiry::forVendor($vendor->id)->whereNull('read_at')->count();
    $vpNav = [
        ['Dashboard', 'vendor.dashboard', 'fa-solid fa-table-cells-large', ['vendor.dashboard']],
        ['Inventory', 'vendor.inventory', 'fa-solid fa-list', ['vendor.inventory', 'vendor.media.show', 'vendor.media.edit']],
        ['Add Media', 'vendor.media.create', 'fa-solid fa-plus', ['vendor.media.create']],
        ['Availability', 'vendor.availability', 'fa-regular fa-calendar', ['vendor.availability']],
        ['Operations', 'vendor.operations', 'fa-solid fa-list-check', ['vendor.operations']],
        ['Bookings', 'vendor.bookings', 'fa-regular fa-clipboard', ['vendor.bookings']],
        ['Reports', 'vendor.reports.utilisation', 'fa-solid fa-chart-column', ['vendor.reports.utilisation']],
        ['Enquiries', 'vendor.enquiries', 'fa-regular fa-envelope', ['vendor.enquiries'], $vpNewEnquiries],
        ['Website & Slider', 'vendor.website', 'fa-solid fa-globe', ['vendor.website']],
        ['Notifications & Log', 'vendor.notifications', 'fa-regular fa-bell', ['vendor.notifications'], $vpUnread],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vendor Panel') · Brand Adda</title>
    <link rel="icon" type="image/webp" href="{{ asset('assets/img/logo/brand_adda_browser.webp') }}">
    {{-- The admin theme is loaded because the shared Media Management form is
         built on it (Bootstrap 4 grid, form-control, modals). --}}
    <link href="{{ asset('asset/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('asset/css/vendor-panel.css') }}?v={{ @filemtime(public_path('asset/css/vendor-panel.css')) }}" rel="stylesheet">
    <script src="{{ asset('asset/plugins/jquery/jquery-3.6.0.min.js') }}"></script>
    @yield('styles')
</head>

<body class="vp-body">
    <div class="vp-shell">
        <aside class="vp-side">
            <img src="{{ asset('assets/img/logo/brand_adda_light.png') }}" alt="Brand Adda" class="vp-side-logo">
            <div class="vp-side-tag">Vendor Panel</div>
            <div class="vp-vendor-chip">
                <div class="vp-mono">
                    @if ($vendor->logo_url)
                        <img src="{{ $vendor->logo_url }}" alt="">
                    @else
                        {{ $vendor->initials }}
                    @endif
                </div>
                <div style="min-width:0">
                    <div class="vp-vendor-name">{{ $vendor->vendor_name }}</div>
                    <div class="vp-vendor-id">Vendor ID · {{ $vendor->vendor_code }}</div>
                </div>
            </div>
            <nav class="vp-nav" aria-label="Vendor panel">
                @foreach ($vpNav as $item)
                    <a href="{{ route($item[1]) }}" class="{{ request()->routeIs(...$item[3]) ? 'active' : '' }}">
                        <i class="{{ $item[2] }}" aria-hidden="true"></i>
                        <span>{{ $item[0] }}</span>
                        @if (!empty($item[4]))
                            <span class="vp-count">{{ $item[4] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
            <div class="vp-side-foot">Powered by Brand Adda</div>
        </aside>

        <main class="vp-main">
            <header class="vp-top">
                <div>
                    <div class="vp-crumb">@yield('crumb', 'Vendor Panel')</div>
                    <h1 class="vp-title">@yield('heading', 'Dashboard')</h1>
                </div>
                <div class="vp-top-actions">
                    @yield('actions')
                    <a href="{{ route('vendor.notifications') }}" class="vp-bell"
                        aria-label="Notifications{{ $vpUnread ? ', ' . $vpUnread . ' unread' : '' }}">
                        <i class="fa-regular fa-bell" aria-hidden="true"></i>
                        @if ($vpUnread)
                            <span class="vp-dot">{{ $vpUnread }}</span>
                        @endif
                    </a>
                    <div class="vp-user">
                        <div class="vp-avatar">{{ $vendor->initials }}</div>
                        <div>
                            <div style="font-size:14px;font-weight:700">{{ $vendor->vendor_name }}</div>
                            <div class="vp-muted" style="font-size:12px">Vendor Admin</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('vendor.logout') }}" style="margin:0">
                        @csrf
                        <button type="submit" class="vp-btn vp-btn-g">Logout</button>
                    </form>
                </div>
            </header>

            <div class="vp-content">
                @if (session('success'))
                    <div class="vp-alert vp-alert-success" role="status">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="vp-alert vp-alert-error" role="alert">{{ session('error') }}</div>
                @endif
                @if ($errors->any() && !View::hasSection('form_errors_inline'))
                    <div class="vp-alert vp-alert-error" role="alert">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script src="{{ asset('asset/plugins/popper/popper.min.js') }}"></script>
    <script src="{{ asset('asset/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('asset/plugins/sweetalert/sweetalert2@11.js') }}"></script>
    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        // Confirm before any form marked data-confirm is submitted.
        $(document).on('submit', 'form[data-confirm]', function (e) {
            const form = this;
            if (form.dataset.confirmed) return;
            e.preventDefault();
            Swal.fire({
                title: form.dataset.confirm,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#F97316',
                cancelButtonColor: '#64748B',
                confirmButtonText: form.dataset.confirmButton || 'Yes, continue'
            }).then(function (r) {
                if (r.isConfirmed) {
                    form.dataset.confirmed = '1';
                    form.submit();
                }
            });
        });
    </script>
    @yield('scripts')
</body>

</html>
