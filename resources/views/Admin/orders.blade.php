@extends('admin.layouts.app')

@section('title', 'Orders')

@push('styles')
<style>
.panel-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
.panel-head h2 { font-size: 20px; }
.live { font-size: 13px; color: var(--muted); }
.live::before { content: ''; display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--ok); margin-right: 6px; }

.tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
.tabs a { text-decoration: none; color: var(--text); background: var(--surface); border: 1px solid var(--line); padding: 7px 14px; border-radius: 99px; font-size: 14px; }
.tabs a span { color: var(--muted); margin-left: 4px; }
.tabs a:hover { background: var(--bg); }
.tabs a.current { background: var(--red); border-color: var(--red); color: #fff; font-weight: 700; }
.tabs a.current span { color: var(--gold); }

.filter-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.filter-row input[type=search] { flex: 1; min-width: 220px; }
input[type=search], input[type=date], select {
  font: inherit; padding: 10px 12px; border: 1px solid var(--line);
  border-radius: 8px; background: #fff; color: var(--text);
}
input:focus, select:focus { border-color: var(--red); outline: 0; box-shadow: 0 0 0 3px rgba(200,0,10,.15); }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; vertical-align: middle; }
th { font-size: 13px; color: var(--muted); font-weight: 500; }
tr:last-child td { border-bottom: 0; }
tr.is-pending td { background: #fffaf0; }
.empty { color: var(--muted); padding: 18px 0; }
.muted { color: var(--muted); font-size: 13px; }

.badge { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 13px; font-weight: 500; background: #eee; color: #444; }
.badge.pending { background: #fff1d6; color: #a86400; }
.badge.confirmed, .badge.preparing { background: #dcebfa; color: #1d5fa8; }
.badge.ready, .badge.completed { background: #d9f1e4; color: var(--ok); }
.badge.out_for_delivery { background: #e8e0f7; color: #5b3aa0; }
.badge.cancelled { background: #fbdcdc; color: #a31212; }
.badge.pay-unpaid { background: #fff1d6; color: #a86400; }
.badge.pay-paid { background: #d9f1e4; color: var(--ok); }
.badge.pay-refunded { background: #e8e0f7; color: #5b3aa0; }
.badge.pay-failed { background: #fbdcdc; color: #a31212; }

.pager { display: flex; justify-content: center; align-items: center; gap: 18px; padding-top: 16px; color: var(--muted); }
</style>
@endpush

@section('content')
@php
  $labels     = \App\Models\Order\Order::STATUS_LABELS;
  $typeLabels = \App\Models\Order\Order::TYPE_LABELS;
  $payLabels  = \App\Models\Order\Order::PAYMENT_LABELS;
  $money      = fn ($v) => 'LKR ' . number_format((float) $v, 2);
  $keep       = array_filter(['q' => $search, 'type' => $type, 'date' => $date]);
@endphp

//Status tabs 
<div class="tabs">
  <a href="{{ route('admin.orders', $keep) }}" class="{{ $status === '' ? 'current' : '' }}">
    All <span>{{ $counts->sum() }}</span>
  </a>
  @foreach ($labels as $key => $label)
    <a href="{{ route('admin.orders', array_merge($keep, ['status' => $key])) }}"
       class="{{ $status === $key ? 'current' : '' }}">
      {{ $label }} <span>{{ $counts[$key] ?? 0 }}</span>
    </a>
  @endforeach
</div>

<div class="panel">
  <div class="panel-head">
    <h2>{{ $orders->total() }} {{ \Illuminate\Support\Str::plural('order', $orders->total()) }}</h2>
    <span class="live">Auto refresh every 60 seconds</span>
  </div>

 //Filters 
  <form method="get" class="filter-row">
    @if ($status !== '') <input type="hidden" name="status" value="{{ $status }}"> @endif

    <input type="search" name="q" value="{{ $search }}" placeholder="Search order number, customer name or phone">

    <select name="type">
      <option value="">All types</option>
      @foreach ($typeLabels as $key => $label)
        <option value="{{ $key }}" {{ $type === $key ? 'selected' : '' }}>{{ $label }}</option>
      @endforeach
    </select>

    <input type="date" name="date" value="{{ $date }}">

    <button class="btn btn-sm" type="submit">Filter</button>
    @if ($search !== '' || $type !== '' || $date !== '' || $status !== '')
      <a class="btn btn-ghost btn-sm" href="{{ route('admin.orders') }}">Clear</a>
    @endif
  </form>

  //Table 
  @if ($orders->isNotEmpty())
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Order</th><th>Customer</th><th>Type</th><th>Items</th><th>Total</th>
            <th>Payment</th><th>Status</th><th>Placed</th><th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($orders as $o)
            <tr class="{{ $o->status === 'pending' ? 'is-pending' : '' }}">
              <td><a href="{{ route('admin.orders.show', $o) }}"><strong>#{{ $o->order_number }}</strong></a></td>
              <td>
                {{ $o->customer->full_name ?? 'Guest' }}
                @if ($o->customer)<div class="muted">{{ $o->customer->phone }}</div>@endif
              </td>
              <td>{{ $typeLabels[$o->order_type] ?? $o->order_type }}</td>
              <td>{{ $o->items_count }}</td>
              <td>{{ $money($o->total_amount) }}</td>
              <td><span class="badge pay-{{ $o->payment_status }}">{{ $payLabels[$o->payment_status] ?? $o->payment_status }}</span></td>
              <td><span class="badge {{ $o->status }}">{{ $labels[$o->status] ?? $o->status }}</span></td>
              <td class="muted">{{ $o->placed_at->format('d M, h:i A') }}</td>
              <td><a href="{{ route('admin.orders.show', $o) }}">View</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if ($orders->hasPages())
      <div class="pager">
        @if ($orders->onFirstPage())
          <span>&larr; Previous</span>
        @else
          <a href="{{ $orders->previousPageUrl() }}">&larr; Previous</a>
        @endif

        <span>Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>

        @if ($orders->hasMorePages())
          <a href="{{ $orders->nextPageUrl() }}">Next &rarr;</a>
        @else
          <span>Next &rarr;</span>
        @endif
      </div>
    @endif
  @else
    <p class="empty">No orders found. New orders from the website and app will appear here.</p>
  @endif
</div>
@endsection

@push('scripts')
<script>
  // reload the list every 60 seconds so new orders show up (not while typing)
  setInterval(function () {
    var tag = document.activeElement ? document.activeElement.tagName : '';
    if (document.visibilityState === 'visible' && tag !== 'INPUT' && tag !== 'SELECT') {
      location.reload();
    }
  }, 60000);
</script>
@endpush