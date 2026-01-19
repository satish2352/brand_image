<style>
    /* Make POST form button behave like sidebar <a> */
    .sidebar-form {
        width: 100%;
    }

    .sidebar-link {
        width: 100%;
        background: transparent;
        border: none;
        padding: 12px 20px;
        text-align: left;
        color: inherit;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        cursor: pointer;
    }

    /* Hover effect */
    .sidebar-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* Active state */
    .nav-item.active .sidebar-link {
        background-color: #008f97;
        /* same as active menu */
        color: #fff;
    }

    /* Remove button outline */
    .sidebar-link:focus {
        outline: none;
        box-shadow: none;
    }



    .nav-item.has-sub .submenu {
    display: none;
    }

    .nav-item.has-sub.open .submenu {
        display: block;
    }

</style>
<nav class="sidebar-nav">
    <ul id="sidebarnav">

        <li class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}">
                <i class="mdi mdi-view-dashboard"></i>
                <span>Dashboard</span>
            </a>
        </li>

        {{-- <li
            class="nav-item has-sub
            {{ request()->is('area/list*') || request()->is('illumination/list*') ? 'active open' : '' }}"> --}}
        @php
            $masterRoutes = request()->is('home-slider/*')
                || request()->is('city/*')
                || request()->is('area/*')
                || request()->is('illumination/*')
                || request()->is('category/*')
                || request()->is('radius/*')
                || request()->is('vendor/*');
        @endphp

        <li
            class="nav-item has-sub {{ $masterRoutes ? 'active open' : '' }}">
            <a href="javascript:void(0)" class="has-sub-toggle">
                <i class="mdi mdi-file-chart"></i>
                <span>Master</span>
                <i class="mdi mdi-chevron-down float-end"></i>
            </a>

            <ul class="submenu">
                <li
                    class="{{ request()->is('home-slider/list') || request()->is('home-slider/add') ? 'active' : '' }}">
                    <a href="{{ route('homeslider.list') }}">
                        <i class="mdi mdi-image-multiple"></i>
                        <span>Home Slider</span>
                    </a>
                </li>
                <li
                    class="{{ request()->is('city/list') || request()->is('city/add') || request()->is('city/edit/*') ? 'active' : '' }}">
                    <a href="{{ route('city.list') }}">
                        <i class="mdi mdi-domain"></i>
                        <span>City</span>
                    </a>
                </li>
                <li
                    class="{{ request()->is('area/list') || request()->is('area/add') || request()->is('area/edit/*') ? 'active' : '' }}">
                    <a href="{{ route('area.list') }}">
                        <i class="mdi mdi-domain"></i>
                        <span>Area</span>
                    </a>
                </li>
                <li
                    class="{{ request()->is('illumination/list') || request()->is('illumination/add') || request()->is('illumination/edit/*') ? 'active' : '' }}">
                    <a href="{{ route('illumination.list') }}">
                        <i class="mdi mdi-store"></i>
                        <span>Illumination</span>
                    </a>
                </li>
                <li
                    class="{{ request()->is('category/list') || request()->is('category/add') || request()->is('category/edit/*') ? 'active' : '' }}">
                    <a href="{{ route('category.list') }}">
                        <i class="mdi mdi-domain"></i>
                        <span>Category</span>
                    </a>
                </li>
                <li
                    class="{{ request()->is('radius/list') || request()->is('radius/add') || request()->is('radius/edit/*') ? 'active' : '' }}">
                    <a href="{{ route('radius.list') }}">
                        <i class="mdi mdi-radius"></i>
                        <span>Radius</span>
                    </a>
                </li>
                <li
                    class="{{ request()->is('vendor/list') || request()->is('vendor/add') || request()->is('vendor/edit/*') ? 'active' : '' }}">
                    <a href="{{ route('vendor.list') }}">
                        <i class="mdi mdi-store"></i>
                        <span>Vendor</span>
                    </a>
                </li>
            </ul>
        </li>



        <li
            class="nav-item {{ request()->is('media/list') || request()->is('media/add') || request()->is('media/edit/*') ? 'active' : '' }}">
            <a href="{{ route('media.list') }}">
                <i class="mdi mdi-domain"></i>
                <span>Media Management</span>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('admin-booking.index') ? 'active' : '' }}">
            <a href="{{ route('admin-booking.index') }}">
                <i class="mdi mdi-calendar-check"></i>
                <span>Admin Booking</span>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('admin.booking.list-booking') ? 'active' : '' }}">
            <form action="{{ route('admin.booking.list-booking') }}" method="POST" class="sidebar-form">
                @csrf
                <button type="submit" class="sidebar-link">
                    <i class="mdi mdi-calendar-check"></i>
                    <span> Booking List</span>
                </button>
            </form>
        </li>




        <li class="nav-item {{ request()->is('admin-campaing/list') ? 'active' : '' }}">
            <a href="{{ route('admin-campaing.list') }}">
                <i class="mdi mdi-account-key"></i>
                <span>Campaign List</span>
            </a>
        </li>
        <li
            class="nav-item {{ request()->is('website-user/list') || request()->is('website-user/add') || request()->is('website-user/edit/*') ? 'active' : '' }}">
            <a href="{{ route('website-user.list') }}">
                <i class="mdi mdi-account-key"></i>
                <span>Website User</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('contact-us/list') }}">
            <a href="{{ route('contact-us.list') }}">
                <i class="mdi mdi-account-key"></i>
                <span>Contact Us</span>
            </a>
        </li>
        <li
            class="nav-item {{ request()->is('user-payment/list') || request()->is('user-payment/details/*') ? 'active' : '' }}">
            <a href="{{ route('user-payment.list') }}">
                <i class="mdi mdi-account-key"></i>
                <span>User Payment</span>
            </a>
        </li>
        {{-- <li class="nav-item {{ request()->routeIs('reports.media.utilisation') ? 'active' : '' }}">
            <a href="{{ route('reports.media.utilisation') }}">
                <i class="mdi mdi-account-key"></i>
                <span>Media Utilisation</span>
            </a>
        </li> --}}
        <li
            class="nav-item has-sub
            {{ request()->is('reports/revenue*') || request()->is('reports/media*') ? 'active open' : '' }}">

            <a href="#">
                <i class="mdi mdi-file-chart"></i>
                <span>Reports</span>
                <i class="mdi mdi-chevron-down float-end"></i>
            </a>

            <ul class="submenu">
                <li class="{{ request()->is('reports/media*') ? 'active' : '' }}">
                    <a href="{{ route('reports.media.utilisation') }}">
                        Media Utilisation
                    </a>
                </li>

                <li class="{{ request()->is('reports/revenue*') ? 'active' : '' }}">
                    <a href="{{ route('reports.revenue.index') }}">
                        Revenue Report
                    </a>
                </li>

                <li class="{{ request()->is('reports/revenue-graph') ? 'active' : '' }}">
                    <a href="{{ route('reports.revenue.graph') }}">
                        📊 Revenue Graph
                    </a>
                </li>
            </ul>
        </li>
        {{-- <li class="nav-item">
            <a href="{{ route('admin.logout') }}">
                <i class="mdi mdi-logout"></i>
                <span>Logout</span>
            </a>
        </li> --}}
        <li class="nav-item">
            <a href="javascript:void(0)" class="logout-btn">
                <i class="mdi mdi-logout"></i>
                <span>Logout</span>
            </a>
        </li>


    </ul>
</nav>

<script>
    $(document).on('click', '.logout-btn', function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#b0302a',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Logout',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('admin.logout') }}";
            }
        });
    });
</script>

<script>
    $(document).on('click', '.has-sub-toggle', function (e) {
        e.preventDefault();

        let parent = $(this).closest('.has-sub');

        // close other open submenus (optional)
        $('.nav-item.has-sub').not(parent).removeClass('open');

        parent.toggleClass('open');
    });
</script>
