<aside class="sidebar" id="sidebar">
  <div class="brand">
    <span class="brand-small">MyBatticaloa</span>
    <span class="brand-big">China Town</span>
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
  </nav>
</aside>