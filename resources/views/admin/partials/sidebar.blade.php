@php $me = auth('admin')->user(); @endphp

<aside class="sidebar" id="sidebar">
  <div class="brand">
    <span class="brand-small">MyBatticaloa</span>
    <span class="brand-big">Golden Dragon</span>
  </div>

  <nav>
    @if ($me->can('dashboard.view'))
      <div class="nav-group">Overview</div>
      <a href="{{ route('admin.dashboard') }}"
         class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
    @endif

    @if ($me->can('orders.view') || $me->can('reservations.view'))
      <div class="nav-group">Sales</div>

      @if ($me->can('orders.view'))
        <a href="{{ route('admin.orders') }}"
           class="{{ request()->routeIs('admin.orders*') ? 'active' : '' }}">Orders</a>
      @endif

      @if ($me->can('reservations.view'))
        <a href="{{ route('admin.reservations') }}"
           class="{{ request()->routeIs('admin.reservations*') ? 'active' : '' }}">Reservations</a>
        <a href="{{ route('admin.tables') }}"
           class="{{ request()->routeIs('admin.tables*') ? 'active' : '' }}">Tables</a>
      @endif
    @endif

    @if ($me->can('menu.view'))
      <div class="nav-group">Menu</div>
      <a href="{{ route('admin.menu') }}"
         class="{{ request()->routeIs('admin.menu*') || request()->routeIs('admin.categories*') ? 'active' : '' }}">Menu items</a>
    @endif

    @if ($me->can('customers.view'))
      <div class="nav-group">People</div>
      <a href="{{ route('admin.customers') }}"
         class="{{ request()->routeIs('admin.customers*') ? 'active' : '' }}">Customers</a>
    @endif

    @if ($me->can('users.view') || $me->hasRole('super_admin'))
      <div class="nav-group">Admin</div>

      @if ($me->can('users.view'))
        <a href="{{ route('admin.users') }}"
           class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">Admin users</a>
      @endif

      @if ($me->hasRole('super_admin'))
        <a href="{{ route('admin.roles') }}"
           class="{{ request()->routeIs('admin.roles*') ? 'active' : '' }}">Roles &amp; permissions</a>
      @endif
    @endif
  </nav>
</aside>