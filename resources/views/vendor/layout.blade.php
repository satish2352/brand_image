@php
    /** @var \App\Models\Vendor $vendor */
    $vendor = auth('vendor')->user();
    $vpUnread = \App\Models\PanelNotification::forVendor($vendor->id)->where('is_read', false)->count();
    $vpNewEnquiries = \App\Models\VendorEnquiry::forVendor($vendor->id)->whereNull('read_at')->count();
    $vpNav = [
        ['Dashboard', 'vendor.dashboard', 'fa-solid fa-table-cells-large', ['vendor.dashboard']],
        // Add Media is reached from the Media Management page and the dashboard, so it has no menu item of its own.
        ['Media Management', 'vendor.inventory', 'fa-solid fa-list', ['vendor.inventory', 'vendor.media.show', 'vendor.media.edit', 'vendor.media.create']],
        ['Availability', 'vendor.availability', 'fa-regular fa-calendar', ['vendor.availability']],
        ['Operations', 'vendor.operations', 'fa-solid fa-list-check', ['vendor.operations']],
        ['Bookings', 'vendor.bookings', 'fa-regular fa-clipboard', ['vendor.bookings']],
        ['Reports', 'vendor.reports.utilisation', 'fa-solid fa-chart-column', ['vendor.reports.utilisation']],
        ['Enquiries', 'vendor.enquiries', 'fa-regular fa-envelope', ['vendor.enquiries'], $vpNewEnquiries],
        ['Website & Slider', 'vendor.website', 'fa-solid fa-globe', ['vendor.website']],
        ['Notifications', 'vendor.notifications', 'fa-regular fa-bell', ['vendor.notifications'], $vpUnread],
        ['Activity Log', 'vendor.activity', 'fa-solid fa-clock-rotate-left', ['vendor.activity']],
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
    @stack('vendor-head')
</head>

<body class="vp-body">
    {{-- Restore the collapsed sidebar before first paint (no flicker). --}}
    <script>
        if (window.innerWidth > 820 && localStorage.getItem('vpSidebar') === 'collapsed') {
            document.body.classList.add('vp-collapsed');
        }
    </script>
    <div class="vp-backdrop" id="vpBackdrop" hidden></div>
    <div class="vp-shell">
        <aside class="vp-side" id="vpSide">
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
                    <div class="vp-vendor-name">{{ $vendor->display_name }}</div>
                    <div class="vp-vendor-id">Vendor ID · {{ $vendor->vendor_code }}</div>
                </div>
            </div>
            <nav class="vp-nav" aria-label="Vendor panel">
                @foreach ($vpNav as $item)
                    <a href="{{ route($item[1]) }}" class="{{ request()->routeIs(...$item[3]) ? 'active' : '' }}" title="{{ $item[0] }}">
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
                <div style="display:flex;align-items:center;gap:14px;min-width:0">
                    <button type="button" class="vp-toggle" id="vpToggle" aria-controls="vpSide" aria-expanded="true" aria-label="Collapse menu" title="Collapse menu">
                        <i class="fa-solid fa-bars" aria-hidden="true"></i>
                    </button>
                    <div style="min-width:0">
                        <div class="vp-crumb">@yield('crumb', 'Vendor Panel')</div>
                        <h1 class="vp-title">@yield('heading', 'Dashboard')</h1>
                    </div>
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
                            <div style="font-size:14px;font-weight:700">{{ $vendor->display_name }}</div>
                            <div class="vp-muted" style="font-size:12px">Vendor Admin</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('vendor.logout') }}" class="vp-logout" style="margin:0">
                        @csrf
                        <button type="submit" class="vp-btn vp-btn-g" aria-label="Logout" title="Logout"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Logout</span></button>
                    </form>
                </div>
            </header>

            <div class="vp-content">
                @if (session('success'))
                    <div class="vp-alert vp-alert-success" role="status">{{ session('success') }}</div>
                @endif
                @if (session('info'))
                    <div class="vp-alert vp-alert-info" role="status">{{ session('info') }}</div>
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

        // Sidebar toggle: collapse to icons on desktop (remembered), slide-in menu on phones.
        (function () {
            const body = document.body, btn = document.getElementById('vpToggle'), backdrop = document.getElementById('vpBackdrop');
            const mobile = () => window.innerWidth <= 820;

            function sync() {
                const open = mobile() ? body.classList.contains('vp-menu-open') : !body.classList.contains('vp-collapsed');
                const label = mobile() ? (open ? 'Close menu' : 'Open menu') : (open ? 'Collapse menu' : 'Expand menu');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                btn.setAttribute('aria-label', label);
                btn.title = label;
                backdrop.hidden = !(mobile() && open);
            }

            btn.addEventListener('click', function () {
                if (mobile()) {
                    body.classList.toggle('vp-menu-open');
                } else {
                    body.classList.toggle('vp-collapsed');
                    localStorage.setItem('vpSidebar', body.classList.contains('vp-collapsed') ? 'collapsed' : 'expanded');
                }
                sync();
            });
            backdrop.addEventListener('click', function () { body.classList.remove('vp-menu-open'); sync(); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && body.classList.contains('vp-menu-open')) { body.classList.remove('vp-menu-open'); sync(); btn.focus(); }
            });
            window.addEventListener('resize', function () {
                if (mobile()) {
                    body.classList.remove('vp-collapsed');
                } else {
                    body.classList.remove('vp-menu-open');
                    body.classList.toggle('vp-collapsed', localStorage.getItem('vpSidebar') === 'collapsed');
                }
                sync();
            });
            sync();
        })();

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
    @stack('vendor-scripts')
</body>

</html>
