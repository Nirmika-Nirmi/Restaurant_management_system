<aside class="sidebar" id="sidebar">
  <div class="brand">
    <span class="brand-small">MyBatticaloa</span>
    <span class="brand-big">Golden Dragon</span>
  </div>

  <nav>
    <div class="nav-group">Overview</div>
    <a href="{{ route('admin.dashboard') }}"
       class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>

    <div class="nav-group">Menu</div>
    <a href="{{ route('admin.menu') }}"
       class="{{ request()->routeIs('admin.menu*') || request()->routeIs('admin.categories*') ? 'active' : '' }}">Menu items</a>

    <div class="nav-group">People</div>
    <a href="{{ route('admin.customers') }}"
       class="{{ request()->routeIs('admin.customers*') ? 'active' : '' }}">Customers</a>


            <div class="nav-group">Admin</div>
    <a href="{{ route('admin.users') }}"
       class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">Admin users</a>
    <a href="{{ route('admin.roles') }}"
       class="{{ request()->routeIs('admin.roles*') ? 'active' : '' }}">Roles &amp; permissions</a>
  </nav>
</aside>