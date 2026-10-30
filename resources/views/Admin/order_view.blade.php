@extends('admin.layouts.app')

@section('title', 'Order #' . $order->order_number)

@push('styles')
<style>
.back-link { margin: 0 0 14px; font-size: 14px; }
.panel-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
.panel-head h2 { font-size: 20px; }
.detail-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start; }
.detail-grid .panel { margin-bottom: 20px; }

.order-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
.order-head h2 { font-size: 26px; }
.order-head .muted { color: var(--muted); font-size: 14px; }

.kv { display: grid; grid-template-columns: 110px 1fr; gap: 8px 12px; margin: 0; }
.kv dt { color: var(--muted); }
.kv dd { margin: 0; word-break: break-word; }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 11px 10px; border-bottom: 1px solid var(--line); vertical-align: top; }
th { font-size: 13px; color: var(--muted); font-weight: 500; white-space: nowrap; }
tr:last-child td { border-bottom: 0; }
td.num, th.num { text-align: right; white-space: nowrap; }
.sub { color: var(--muted); font-size: 13px; }

.totals { margin: 16px 0 0; border-top: 1px solid var(--line); padding-top: 12px; }
.totals div { display: flex; justify-content: space-between; padding: 4px 0; }
.totals dt, .totals dd { margin: 0; }
.totals .grand { font-weight: 700; font-size: 18px; border-top: 1px solid var(--line); margin-top: 6px; padding-top: 10px; }

.note-box { margin-top: 14px; padding: 12px 14px; background: #fff1d6; color: #7a4a00; border-radius: 8px; }
.cancel-box { margin-top: 14px; padding: 12px 14px; background: #fbdcdc; color: #8a1010; border-radius: 8px; }

.timeline { list-style: none; margin: 0; padding: 0; }
.timeline li { position: relative; padding: 0 0 16px 22px; border-left: 2px solid var(--line); margin-left: 6px; }
.timeline li:last-child { border-left-color: transparent; padding-bottom: 0; }
.timeline li::before { content: ''; position: absolute; left: -7px; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: var(--red); }
.timeline .when { color: var(--muted); font-size: 13px; }

.field { margin-bottom: 12px; }
.field label { display: block; font-weight: 500; margin-bottom: 6px; }
input[type=text], select {
  width: 100%; font: inherit; padding: 10px 12px; border: 1px solid var(--line);
  border-radius: 8px; background: #fff; color: var(--text);
}
input:focus, select:focus { border-color: var(--red); outline: 0; box-shadow: 0 0 0 3px rgba(200,0,10,.15); }
.action-form { margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px solid var(--line); }
.action-form:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: 0; }
.action-form h3 { font-size: 16px; margin-bottom: 10px; }
.btn-full { width: 100%; margin-bottom: 8px; }
.btn-danger { background: #a31212; }
.btn-danger:hover { background: #7d0d0d; }

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

@media (max-width: 900px) { .detail-grid { grid-template-columns: 1fr; } }

@media print {
  .sidebar, .topbar, .no-print { display: none !important; }
  body { background: #fff; }
  .content { padding: 0; max-width: none; }
  .panel { border: 1px solid #ccc; break-inside: avoid; }
  .detail-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
@php
  $me         = auth('admin')->user();
  $labels     = \App\Models\Order\Order::STATUS_LABELS;
  $typeLabels = \App\Models\Order\Order::TYPE_LABELS;
  $payLabels  = \App\Models\Order\Order::PAYMENT_LABELS;
  $money      = fn ($v) => 'LKR ' . number_format((float) $v, 2);
  $next       = $order->nextStatuses();
  $canStatus  = $me->can('orders.status') && ! empty($next);
  $canCancel  = $me->can('orders.manage') && $order->canCancel();
  $canPay     = $me->can('orders.manage')
                && in_array($order->payment_status, ['unpaid', 'failed'], true)
                && $order->status !== 'cancelled';
@endphp

<p class="back-link no-print"><a href="{{ route('admin.orders') }}">&larr; Back to orders</a></p>

//Header
<div class="panel">
  <div class="order-head">
    <div>
      <h2>Order #{{ $order->order_number }}</h2>
      <div class="muted">
        {{ $typeLabels[$order->order_type] ?? $order->order_type }}
        &middot; placed {{ $order->placed_at->format('d M Y, h:i A') }}
      </div>
    </div>
    <div>
      <span class="badge {{ $order->status }}">{{ $labels[$order->status] ?? $order->status }}</span>
      <span class="badge pay-{{ $order->payment_status }}">{{ $payLabels[$order->payment_status] ?? $order->payment_status }}</span>
    </div>
  </div>

  @if ($order->status === 'cancelled' && $order->cancel_reason)
    <div class="cancel-box"><strong>Cancelled:</strong> {{ $order->cancel_reason }}</div>
  @endif
</div>

<div class="detail-grid">

  //Left column
  <div>

    //Items 
    <div class="panel">
      <div class="panel-head"><h2>Items</h2></div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Item</th><th class="num">Price</th><th class="num">Qty</th><th class="num">Total</th></tr>
          </thead>
          <tbody>
            @foreach ($order->items as $item)
              <tr>
                <td>
                  <strong>{{ $item->item_name }}</strong>
                  @if ($item->variant_name) <span class="sub">({{ $item->variant_name }})</span> @endif
                  @if ($item->is_free_item) <span class="badge completed">Free</span> @endif

                  @foreach ($item->addons as $a)
                    <div class="sub">+ {{ $a->addon_name }}@if ($a->price > 0) ({{ $money($a->price) }})@endif</div>
                  @endforeach

                  @if ($item->special_note)
                    <div class="sub">Note: {{ $item->special_note }}</div>
                  @endif
                </td>
                <td class="num">{{ $money($item->unit_price) }}</td>
                <td class="num">{{ $item->quantity }}</td>
                <td class="num">{{ $money($item->line_total) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <dl class="totals">
        <div><dt>Subtotal</dt><dd>{{ $money($order->subtotal) }}</dd></div>
        @if ($order->discount_amount > 0)
          <div><dt>Discount</dt><dd>- {{ $money($order->discount_amount) }}</dd></div>
        @endif
        @if ($order->tax_amount > 0)
          <div><dt>Tax / service charge</dt><dd>{{ $money($order->tax_amount) }}</dd></div>
        @endif
        @if ($order->delivery_fee > 0)
          <div><dt>Delivery fee</dt><dd>{{ $money($order->delivery_fee) }}</dd></div>
        @endif
        <div class="grand"><dt>Total</dt><dd>{{ $money($order->total_amount) }}</dd></div>
      </dl>

      @if ($order->customer_note)
        <div class="note-box"><strong>Customer note:</strong> {{ $order->customer_note }}</div>
      @endif
    </div>

    //Payments 
    <div class="panel">
      <div class="panel-head"><h2>Payments</h2></div>

      @forelse ($order->payments as $p)
        <div style="padding:8px 0; border-bottom:1px solid var(--line)">
          <strong>{{ $money($p->amount) }}</strong>
          &middot; {{ ucfirst($p->method) }}
          &middot; {{ ucfirst($p->status) }}
          @if ($p->transaction_ref) <span class="sub">&middot; Ref: {{ $p->transaction_ref }}</span> @endif
          <div class="sub">{{ ($p->paid_at ?? $p->created_at)->format('d M Y, h:i A') }}</div>
        </div>
      @empty
        <p class="sub">No payment recorded yet.</p>
      @endforelse
    </div>

    //History 
    <div class="panel">
      <div class="panel-head"><h2>Status history</h2></div>

      <ul class="timeline">
        @foreach ($order->history->reverse() as $h)
          <li>
            <span class="badge {{ $h->status }}">{{ $labels[$h->status] ?? $h->status }}</span>
            <span class="sub">by {{ $h->admin->full_name ?? 'System' }}</span>
            @if ($h->note)<div>{{ $h->note }}</div>@endif
            <div class="when">{{ $h->created_at->format('d M Y, h:i A') }}</div>
          </li>
        @endforeach
        <li>
          <strong>Order placed</strong>
          <div class="when">{{ $order->placed_at->format('d M Y, h:i A') }}</div>
        </li>
      </ul>
    </div>
  </div>

  //Right column
  <div>

    //Customer
    <div class="panel">
      <div class="panel-head"><h2>Customer</h2></div>

      <dl class="kv">
        <dt>Name</dt>
        <dd>
          @if ($order->customer && $me->can('customers.view'))
            <a href="{{ route('admin.customers.show', $order->customer) }}">{{ $order->customer->full_name }}</a>
          @else
            {{ $order->customer->full_name ?? 'Guest' }}
          @endif
        </dd>

        @if ($order->customer)
          <dt>Phone</dt><dd><a href="tel:{{ $order->customer->phone }}">{{ $order->customer->phone }}</a></dd>
          @if ($order->customer->email)
            <dt>Email</dt><dd>{{ $order->customer->email }}</dd>
          @endif
        @endif

        @if ($order->order_type === 'delivery')
          <dt>Address</dt><dd>{{ $order->delivery_address ?: '-' }}</dd>
          @if ($zoneName)<dt>Zone</dt><dd>{{ $zoneName }}</dd>@endif
        @elseif ($order->order_type === 'dine_in')
          <dt>Table</dt><dd>{{ $tableNumber ?: '-' }}</dd>
        @endif

        @if ($order->estimated_minutes)
          <dt>Estimate</dt><dd>{{ $order->estimated_minutes }} minutes</dd>
        @endif
      </dl>
    </div>

    //Actions
    <div class="panel no-print">
      <div class="panel-head"><h2>Actions</h2></div>

      @if ($canStatus)
        <form class="action-form" method="post" action="{{ route('admin.orders.status', $order) }}">
          @csrf
          <h3>Update status</h3>
          <div class="field">
            <label for="note">Note (optional)</label>
            <input type="text" id="note" name="note" maxlength="255">
          </div>
          @foreach ($next as $s)
            <button class="btn btn-full" type="submit" name="status" value="{{ $s }}">
              Mark as {{ $labels[$s] }}
            </button>
          @endforeach
        </form>
      @endif

      @if ($canPay)
        <form class="action-form" method="post" action="{{ route('admin.orders.payment', $order) }}">
          @csrf
          <h3>Record payment</h3>
          <div class="field">
            <label for="method">Method</label>
            <select id="method" name="method" required>
              <option value="cash">Cash</option>
              <option value="card">Card</option>
              <option value="online">Online</option>
              <option value="wallet">Wallet</option>
            </select>
          </div>
          <div class="field">
            <label for="transaction_ref">Reference (optional)</label>
            <input type="text" id="transaction_ref" name="transaction_ref" maxlength="100">
          </div>
          <button class="btn btn-full" type="submit">Mark as paid ({{ $money($order->total_amount) }})</button>
        </form>
      @endif

      @if ($canCancel)
        <form class="action-form" method="post" action="{{ route('admin.orders.cancel', $order) }}"
              onsubmit="return confirm('Cancel this order?')">
          @csrf
          <h3>Cancel order</h3>
          <div class="field">
            <label for="cancel_reason">Reason</label>
            <input type="text" id="cancel_reason" name="cancel_reason" maxlength="255" required>
          </div>
          <button class="btn btn-danger btn-full" type="submit">Cancel order</button>
        </form>
      @endif

      @unless ($canStatus || $canPay || $canCancel)
        <p class="sub">No actions available for this order.</p>
      @endunless

      <button class="btn btn-ghost btn-full" type="button" onclick="window.print()" style="margin-top:14px">
        Print
      </button>
    </div>

  </div>
</div>
@endsection