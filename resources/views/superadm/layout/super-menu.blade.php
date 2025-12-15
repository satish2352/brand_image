<nav class="sidebar-nav">
    <ul id="sidebarnav">

        <li class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}">
                <i class="mdi mdi-view-dashboard"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('roles/list') || request()->is('roles/add') || request()->is('roles/edit/*') ? 'active' : '' }}">
            <a href="{{ route('roles.list') }}">
                <i class="mdi mdi-account-key"></i>
                <span>Role</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('states/list') || request()->is('states/add') || request()->is('states/edit/*') ? 'active' : '' }}">
            <a href="{{ route('states.list') }}">
                <i class="mdi mdi-domain"></i>
                <span>State</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('districts/list') || request()->is('districts/add') || request()->is('districts/edit/*') ? 'active' : '' }}">
            <a href="{{ route('districts.list') }}">
                <i class="mdi mdi-map-marker"></i>
                <span>District</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('cities/list') || request()->is('cities/add') || request()->is('cities/edit/*') ? 'active' : '' }}">
            <a href="{{ route('cities.list') }}">
                <i class="mdi mdi-city"></i>
                <span>City</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('radius/list') || request()->is('radius/add') || request()->is('radius/edit/*') ? 'active' : '' }}">
            <a href="{{ route('radius.list') }}">
                <i class="mdi mdi-radius"></i>
                <span>Radius</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('media/list') || request()->is('media/add') || request()->is('media/edit/*') ? 'active' : '' }}">
            <a href="{{ route('media.list') }}">
                <i class="mdi mdi-circle"></i>
                <span>Media</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('locations/list') || request()->is('locations/add') || request()->is('locations/edit/*') ? 'active' : '' }}">
            <a href="{{ route('locations.list') }}">
                <i class="mdi mdi-city"></i>
                <span>Location</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('designations/list') || request()->is('designations/add') || request()->is('designations/edit/*') ? 'active' : '' }}">
            <a href="{{ route('designations.list') }}">
                <i class="mdi mdi-badge-account"></i>
                <span>Designations</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('financial-year/list') || request()->is('financial-year/add') || request()->is('financial-year/edit/*') ? 'active' : '' }}">
            <a href="{{ route('financial-year.list') }}">
                <i class="mdi mdi-calendar"></i>
                <span>Financial Years</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('employee-types/list') || request()->is('employee-types/add') || request()->is('employee-types/edit/*') ? 'active' : '' }}">
            <a href="{{ route('employee-types.list') }}">
                <i class="mdi mdi-account-circle"></i>
                <span>Employee Types</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('plantmaster/list') || request()->is('plantmaster/add') || request()->is('plantmaster/edit/*') ? 'active' : '' }}">
            <a href="{{ route('plantmaster.list') }}">
                <i class="mdi mdi-factory"></i>
                <span>Plant</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('projects/list') || request()->is('projects/add') || request()->is('projects/edit/*') ? 'active' : '' }}">
            <a href="{{ route('projects.list') }}">
                <i class="mdi mdi-briefcase"></i>
                <span>Projects</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('departments/list') || request()->is('departments/add') || request()->is('departments/edit/*') ? 'active' : '' }}">
            <a href="{{ route('departments.list') }}">
                <i class="mdi mdi-office-building"></i>
                <span>Departments</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('employees/list') || request()->is('employees/add') || request()->is('employees/edit/*') ? 'active' : '' }}">
            <a href="{{ route('employees.list') }}">
                <i class="mdi mdi-account-group"></i>
                <span>Employees</span>
            </a>
        </li>

        <li class="nav-item {{ request()->is('employee/assignments/list') || request()->is('employee/assignments/add') || request()->is('employee/assignments/edit/*') ? 'active' : '' }}">
            <a href="{{ route('employee.assignments.list') }}">
                <i class="mdi mdi-account-switch"></i>
                <span>Assign Plant</span>
            </a>
        </li>

        <li class="nav-item">
            @if(session('role') == 'admin')
                <a href="{{ route('admin.logout') }}">
                    <i class="mdi mdi-logout"></i>
                    <span>Logout</span>
                </a>
            @else
                <a href="{{ route('emp.logout') }}">
                    <i class="mdi mdi-logout"></i>
                    <span>Logout</span>
                </a>
            @endif
        </li>

    </ul>
</nav>
