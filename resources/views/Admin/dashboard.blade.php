@extends('admin.layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
/*   Welcome   */
.welcome { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 22px; }
.welcome h2 { font-size: 26px; }
.welcome p { margin: 2px 0 0; color: var(--muted); }
.role-chip { display: inline-block; padding: 4px 12px; border-radius: 99px; background: #fbe9ea; color: var(--red); font-size: 13px; font-weight: 700; text-transform: capitalize; }

/*   Stat tiles   */
.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 18px 20px; }
.stat .label { color: var(--muted); font-size: 14px; }
.stat .value { font-family: 'Playfair Display', serif; font-size: 30px; font-weight: 700; margin-top: 4px; }
.stat .sub { font-size: 13px; color: var(--muted); margin-top: 2px; }
.stat.primary { background: var(--red); border-color: var(--red); color: #fff; }
.stat.primary .label { color: rgba(255,255,255,.85); }
.stat.primary .value { color: var(--gold); }
.stat.primary .sub { color: rgba(255,255,255,.8); }

/*   Panels   */
.grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
.grid-2 .panel { margin-bottom: 0; }
.panel-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
.panel-head h2 { font-size: 20px; }
.panel-head a { font-size: 14px; }

/*   Week chart   */
.chart { display: flex; align-items: flex-end; gap: 12px; height: 190px; padding-top: 10px; }
.bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
.bar { width: 100%; max-width: 54px; background: var(--red); border-radius: 6px 6px 0 0; min-height: 3px; }
.bar-col.today .bar { background: var(--gold); }
.bar-amt { font-size: 12px; color: var(--muted); margin-bottom: 4px; }
.bar-day { font-size: 13px; margin-top: 8px; color: var(--muted); }

/*   Best sellers   */
.rank { list-style: none; margin: 0; padding: 0; }
.rank li { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); }
.rank li:last-child { border-bottom: 0; }
.rank .qty { font-weight: 700; }

/*   Table   */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 11px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; }
th { font-size: 13px; color: var(--muted); font-weight: 500; }
tr:last-child td { border-bottom: 0; }
.empty { color: var(--muted); padding: 18px 0; }

/*   Status badges   */
.badge { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 13px; font-weight: 500; background: #eee; color: #444; }
.badge.pending { background: #fff1d6; color: #a86400; }
.badge.confirmed, .badge.preparing { background: #dcebfa; color: #1d5fa8; }
.badge.ready, .badge.completed { background: #d9f1e4; color: var(--ok); }
.badge.out_for_delivery { background: #e8e0f7; color: #5b3aa0; }
.badge.cancelled { background: #fbdcdc; color: #a31212; }

@media (max-width: 900px) {
  .grid-2 { grid-template-columns: 1fr; }
  .grid-2 .panel { margin-bottom: 20px; }
}
</style>
@endpush

@section('content')
@php
  $statusLabels = [
    'pending' => 'Pending', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing',
    'ready' => 'Ready', 'out_for_delivery' => 'Out for delivery',
    'completed' => 'Completed', 'cancelled' => 'Cancelled',
  ];
  $typeLabels = ['dine_in' => 'Dine-in', 'pickup' => 'Pickup', 'delivery' => 'Delivery'];
  $money = fn ($v) => 'LKR ' . number_format((float) $v, 2);
@endphp


<div class="welcome">
  <div>
    <h2>Welcome, {{ $admin->full_name }}</h2>
    <p>{{ now()->format('l, d F Y') }}</p>
  </div>
  <span class="role-chip">{{ str_replace('_', ' ', $admin->role->name ?? 'admin') }}</span>
</div>


<section class="stats">
  <div class="stat primary">
    <div class="label">Today's revenue</div>
    <div class="value">{{ $money($today->revenue) }}</div>
    <div class="sub">{{ (int) $today->orders }} orders today</div>
  </div>

  <div class="stat">
    <div class="label">Pending orders</div>
    <div class="value">{{ $pendingOrders }}</div>
    <div class="sub">Waiting for confirmation</div>
  </div>

  <div class="stat">
    <div class="label">Pending reservations</div>
    <div class="value">{{ $upcomingRes }}</div>
    <div class="sub">Upcoming bookings</div>
  </div>

  <div class="stat">
    <div class="label">Messages this week</div>
    <div class="value">{{ $newMessages }}</div>
    <div class="sub">From contact form</div>
  </div>

  <div class="stat">
    <div class="label">Available menu items</div>
    <div class="value">{{ $menuCount }}</div>
    <div class="sub"><a href="{{ route('admin.menu') }}">Manage menu</a></div>
  </div>

  <div class="stat">
    <div class="label">Registered customers</div>
    <div class="value">{{ $totalCustomers }}</div>
    <div class="sub"><a href="{{ route('admin.customers') }}">View customers</a></div>
  </div>
</section>


<section class="grid-2">
  <div class="panel">
    <div class="panel-head"><h2>Revenue, last 7 days</h2></div>
    <div class="chart">
      @foreach ($week as $w)
        <div class="bar-col {{ $w['today'] ? 'today' : '' }}">
          <span class="bar-amt">{{ $w['amount'] > 0 ? number_format($w['amount']) : '' }}</span>
          <div class="bar" style="height: {{ round(($w['amount'] / $maxAmount) * 100) }}%"></div>
          <span class="bar-day">{{ $w['day'] }}</span>
        </div>
      @endforeach
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Best sellers, 30 days</h2></div>
    @if ($topItems->isNotEmpty())
      <ul class="rank">
        @foreach ($topItems as $t)
          <li><span>{{ $t->name }}</span><span class="qty">{{ (int) $t->qty }} sold</span></li>
        @endforeach
      </ul>
    @else
      <p class="empty">No sales yet. Best sellers appear here after the first orders.</p>
    @endif
  </div>
</section>

<section class="panel">
  <div class="panel-head"><h2>Recent orders</h2></div>

  @if ($recent->isNotEmpty())
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Order</th><th>Customer</th><th>Type</th><th>Status</th><th>Total</th><th>Placed</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($recent as $o)
            <tr>
              <td>#{{ $o->order_number }}</td>
              <td>{{ $o->customer }}</td>
              <td>{{ $typeLabels[$o->order_type] ?? $o->order_type }}</td>
              <td><span class="badge {{ $o->status }}">{{ $statusLabels[$o->status] ?? $o->status }}</span></td>
              <td>{{ $money($o->total_amount) }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($o->placed_at)->format('d M, h:i A') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <p class="empty">No orders yet. New orders from the website and app will show here.</p>
  @endif
</section>
@endsection