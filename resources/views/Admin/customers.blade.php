@extends('admin.layouts.app')

@section('title', 'Customers')

@push('styles')
<style>
.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin-bottom: 20px; }
.stat { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 18px 20px; }
.stat .label { color: var(--muted); font-size: 14px; }
.stat .value { font-family: 'Playfair Display', serif; font-size: 30px; font-weight: 700; margin-top: 4px; }
.stat.primary { background: var(--red); border-color: var(--red); color: #fff; }
.stat.primary .label { color: rgba(255,255,255,.85); }
.stat.primary .value { color: var(--gold); }

.panel-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
.panel-head h2 { font-size: 20px; }

.filter-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.filter-row input[type=search] { flex: 1; min-width: 220px; }
.filter-row select { width: auto; }
input[type=search], select {
  font: inherit; padding: 10px 12px; border: 1px solid var(--line);
  border-radius: 8px; background: #fff; color: var(--text);
}
input:focus, select:focus { border-color: var(--red); outline: 0; box-shadow: 0 0 0 3px rgba(200,0,10,.15); }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 11px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; vertical-align: middle; }
th { font-size: 13px; color: var(--muted); font-weight: 500; }
tr:last-child td { border-bottom: 0; }
.empty { color: var(--muted); padding: 18px 0; }
.muted { color: var(--muted); font-size: 13px; }
.avatar { display: inline-grid; place-items: center; width: 36px; height: 36px; border-radius: 50%; background: #fbe9ea; color: var(--red); font-weight: 700; margin-right: 10px; vertical-align: middle; }

.badge { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 13px; font-weight: 500; background: #eee; color: #444; }
.badge.guest { background: #fff1d6; color: #a86400; }

.toggle { font: inherit; font-size: 13px; font-weight: 700; padding: 3px 12px; border-radius: 99px; border: 1px solid var(--line); background: #eee; color: var(--muted); cursor: pointer; }
.toggle.on { background: #d9f1e4; color: var(--ok); border-color: #b7e0c8; }

.pager { display: flex; justify-content: center; align-items: center; gap: 18px; padding-top: 16px; color: var(--muted); }
</style>
@endpush

@section('content')
@php
  $money = fn ($v) => 'LKR ' . number_format((float) $v, 2);
@endphp

<section class="stats">
  <div class="stat primary">
    <div class="label">Total customers</div>
    <div class="value">{{ $stats['total'] }}</div>
  </div>
  <div class="stat">
    <div class="label">Active customers</div>
    <div class="value">{{ $stats['active'] }}</div>
  </div>
  <div class="stat">
    <div class="label">New this month</div>
    <div class="value">{{ $stats['thisMonth'] }}</div>
  </div>
</section>

<div class="panel">
  <div class="panel-head">
    <h2>{{ $customers->total() }} {{ \Illuminate\Support\Str::plural('customer', $customers->total()) }}</h2>
  </div>

  <form method="get" class="filter-row">
    <input type="search" name="q" value="{{ $search }}" placeholder="Search by name, email or phone">
    <select name="status">
      <option value="">All status</option>
      <option value="active"   {{ $status === 'active' ? 'selected' : '' }}>Active</option>
      <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
    <button class="btn btn-sm" type="submit">Filter</button>
    @if ($search !== '' || $status !== '')
      <a class="btn btn-ghost btn-sm" href="{{ route('admin.customers') }}">Clear</a>
    @endif
  </form>

  @if ($customers->isNotEmpty())
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Customer</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Orders</th>
            <th>Total spent</th>
            <th>Joined</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($customers as $c)
            <tr>
              <td>
                <span class="avatar">{{ strtoupper(mb_substr($c->full_name, 0, 1)) }}</span>
                <strong>{{ $c->full_name }}</strong>
                @if ($c->isGuest()) <span class="badge guest">Guest</span> @endif
              </td>
              <td>{{ $c->phone }}</td>
              <td>{{ $c->email ?: '-' }}</td>
              <td>{{ (int) $c->orders_count }}</td>
              <td>{{ $money($c->total_spent) }}</td>
              <td>{{ $c->created_at->format('d M Y') }}</td>
              <td>
                <form method="post" action="{{ route('admin.customers.toggle', $c->id) }}" style="margin:0">
                  @csrf
                  <button type="submit" class="toggle {{ $c->is_active ? 'on' : '' }}"
                          onclick="return confirm('{{ $c->is_active ? 'Deactivate' : 'Activate' }} this customer?')">
                    {{ $c->is_active ? 'Active' : 'Inactive' }}
                  </button>
                </form>
              </td>
              <td><a href="{{ route('admin.customers.show', $c->id) }}">View</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if ($customers->hasPages())
      <div class="pager">
        @if ($customers->onFirstPage())
          <span>&larr; Previous</span>
        @else
          <a href="{{ $customers->previousPageUrl() }}">&larr; Previous</a>
        @endif

        <span>Page {{ $customers->currentPage() }} of {{ $customers->lastPage() }}</span>

        @if ($customers->hasMorePages())
          <a href="{{ $customers->nextPageUrl() }}">Next &rarr;</a>
        @else
          <span>Next &rarr;</span>
        @endif
      </div>
    @endif
  @else
    <p class="empty">No customers found. Customers appear here after they register on the website or app.</p>
  @endif
</div>
@endsection