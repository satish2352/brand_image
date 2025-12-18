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

      
         <li class="nav-item {{ request()->is('area/list') || request()->is('area/add') || request()->is('area/edit/*') ? 'active' : '' }}">
            <a href="{{ route('area.list') }}">
                <i class="mdi mdi-domain"></i>
                <span>Area</span>
            </a>
        </li>

             <li class="nav-item {{ request()->is('category/list') || request()->is('category/add') || request()->is('category/edit/*') ? 'active' : '' }}">
            <a href="{{ route('category.list') }}">
                <i class="mdi mdi-domain"></i>
                <span>Category</span>
            </a>
        </li>
         <li class="nav-item {{ request()->is('media/list') || request()->is('media/add') || request()->is('media/edit/*') ? 'active' : '' }}">
            <a href="{{ route('media.list') }}">
                <i class="mdi mdi-domain"></i>
                <span>Media Management</span>
            </a>
        </li>
        
        <li class="nav-item {{ request()->is('radius/list') || request()->is('radius/add') || request()->is('radius/edit/*') ? 'active' : '' }}">
            <a href="{{ route('radius.list') }}">
                <i class="mdi mdi-radius"></i>
                <span>Radius</span>
            </a>
        </li>
               <li class="nav-item {{ request()->is('employees/list') || request()->is('employees/add') || request()->is('employees/edit/*') ? 'active' : '' }}">
            <a href="{{ route('employees.list') }}">
                <i class="mdi mdi-account-group"></i>
                <span>Employees</span>
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
