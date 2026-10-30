@extends('admin.layouts.app')

@section('title', 'Customer')

@push('styles')
<style>
.back-link { margin: 0 0 14px; font-size: 14px; }
.detail-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; align-items: start; }
.kv { display: grid; grid-template-columns: 110px 1fr; gap: 8px 12px; margin: 0; }
.kv dt { color: var(--muted); }
.kv dd { margin: 0; word-break: break-word; }
.panel-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
.panel-head h2 { font-size: 20px; }

.mini-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
.mini { background: var(--bg); border-radius: 10px; padding: 12px 14px; }
.mini .label { color: var(--muted); font-size: 13px; }
.mini .value { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; }

.addr { padding: 10px 0; border-bottom: 1px solid var(--line); }
.addr:last-child { border-bottom: 0; }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 11px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; }
th { font-size: 13px; color: var(--muted); font-weight: 500; }
tr:last-child td { border-bottom: 0; }
.empty { color: var(--muted); padding: 10px 0; }

.badge { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 13px; font-weight: 500; background: #eee; color: #444; }
.badge.pending { background: #fff1d6; color: #a86400; }
.badge.confirmed, .badge.preparing { background: #dcebfa; color: #1d5fa8; }
.badge.ready, .badge.completed { background: #d9f1e4; color: var(--ok); }
.badge.out_for_delivery { background: #e8e0f7; color: #5b3aa0; }
.badge.cancelled { background: #fbdcdc; color: #a31212; }
.badge.guest { background: #fff1d6; color: #a86400; }

.toggle { font: inherit; font-size: 13px; font-weight: 700; padding: 3px 12px; border-radius: 99px; border: 1px solid var(--line); background: #eee; color: var(--muted); cursor: pointer; }
.toggle.on { background: #d9f1e4; color: var(--ok); border-color: #b7e0c8; }

@media (max-width: 900px) { .detail-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
@php
  $money = fn ($v) => 'LKR ' . number_format((float) $v, 2);
  $statusLabels = [
    'pending' => 'Pending', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing',
    'ready' => 'Ready', 'out_for_delivery' => 'Out for delivery',
    'completed' => 'Completed', 'cancelled' => 'Cancelled',
  ];
  $typeLabels = ['dine_in' => 'Dine-in', 'pickup' => 'Pickup', 'delivery' => 'Delivery'];
@endphp

<p class="back-link"><a href="{{ route('admin.customers') }}">&larr; Back to customers</a></p>

<div class="detail-grid">


  <div>
    <div class="panel">
      <div class="panel-head">
        <h2>{{ $customer->full_name }}</h2>
        @if ($customer->isGuest()) <span class="badge guest">Guest</span> @endif
      </div>

      <dl class="kv">
        <dt>Phone</dt><dd>{{ $customer->phone }}</dd>
        <dt>Email</dt><dd>{{ $customer->email ?: '-' }}</dd>
        <dt>Joined</dt><dd>{{ $customer->created_at->format('d M Y, h:i A') }}</dd>
        <dt>Status</dt>
        <dd>
          <form method="post" action="{{ route('admin.customers.toggle', $customer) }}" style="margin:0">
            @csrf
            <button type="submit" class="toggle {{ $customer->is_active ? 'on' : '' }}"
                    onclick="return confirm('{{ $customer->is_active ? 'Deactivate' : 'Activate' }} this customer?')">
              {{ $customer->is_active ? 'Active' : 'Inactive' }}
            </button>
          </form>
        </dd>
      </dl>

      <div class="mini-stats">
        <div class="mini">
          <div class="label">Orders</div>
          <div class="value">{{ (int) $totals->orders_count }}</div>
        </div>
        <div class="mini">
          <div class="label">Total spent</div>
          <div class="value">{{ $money($totals->total_spent) }}</div>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Addresses</h2></div>
      @forelse ($addresses as $a)
        <div class="addr">
          <strong>{{ $a->label ?: 'Address' }}</strong>
          @if ($a->is_default) <span class="badge confirmed">Default</span> @endif
          <div>
            {{ $a->address_line1 }}@if ($a->address_line2), {{ $a->address_line2 }}@endif
          </div>
          <div class="muted">{{ $a->city }}@if ($a->postal_code), {{ $a->postal_code }}@endif</div>
        </div>
      @empty
        <p class="empty">No saved addresses.</p>
      @endforelse
    </div>
  </div>


  <div class="panel">
    <div class="panel-head"><h2>Recent orders</h2></div>

    @if ($orders->isNotEmpty())
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Order</th><th>Type</th><th>Status</th><th>Total</th><th>Placed</th></tr>
          </thead>
          <tbody>
            @foreach ($orders as $o)
              <tr>
                <td>#{{ $o->order_number }}</td>
                <td>{{ $typeLabels[$o->order_type] ?? $o->order_type }}</td>
                <td><span class="badge {{ $o->status }}">{{ $statusLabels[$o->status] ?? $o->status }}</span></td>
                <td>{{ $money($o->total_amount) }}</td>
                <td>{{ \Illuminate\Support\Carbon::parse($o->placed_at)->format('d M Y, h:i A') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <p class="empty">This customer has not placed any orders yet.</p>
    @endif
  </div>

</div>
@endsection