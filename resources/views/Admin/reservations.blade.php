@extends('admin.layouts.app')

@section('title', 'Reservations')

@push('styles')
<style>
.panel-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
.panel-head h2 { font-size: 20px; }
.panel-head a { font-size: 14px; }
.form-panel { border-color: var(--red); }
.form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0 16px; }
.form-grid .span-3 { grid-column: span 3; }
.field { margin-bottom: 14px; }
.field label { display: block; font-weight: 500; margin-bottom: 6px; }
input[type=text], input[type=number], input[type=search], input[type=datetime-local], input[type=date], select {
  width: 100%; font: inherit; padding: 10px 12px; border: 1px solid var(--line);
  border-radius: 8px; background: #fff; color: var(--text);
}
input:focus, select:focus { border-color: var(--red); outline: 0; box-shadow: 0 0 0 3px rgba(200,0,10,.15); }

.tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
.tabs a { text-decoration: none; color: var(--text); background: var(--surface); border: 1px solid var(--line); padding: 7px 14px; border-radius: 99px; font-size: 14px; }
.tabs a span { color: var(--muted); margin-left: 4px; }
.tabs a:hover { background: var(--bg); }
.tabs a.current { background: var(--red); border-color: var(--red); color: #fff; font-weight: 700; }
.tabs a.current span { color: var(--gold); }

.filter-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.filter-row input[type=search] { flex: 1; min-width: 220px; width: auto; }
.filter-row select { width: auto; }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; vertical-align: middle; }
th { font-size: 13px; color: var(--muted); font-weight: 500; }
tr:last-child td { border-bottom: 0; }
tr.is-today td { background: #fffaf0; }
.empty { color: var(--muted); padding: 18px 0; }
.muted { color: var(--muted); font-size: 13px; }
.notes { white-space: normal; max-width: 220px; color: var(--muted); font-size: 13px; }

.badge { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 13px; font-weight: 500; background: #eee; color: #444; }
.badge.pending { background: #fff1d6; color: #a86400; }
.badge.confirmed { background: #dcebfa; color: #1d5fa8; }
.badge.seated { background: #e8e0f7; color: #5b3aa0; }
.badge.completed { background: #d9f1e4; color: var(--ok); }
.badge.cancelled, .badge.no_show { background: #fbdcdc; color: #a31212; }

.row-actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.row-actions form { margin: 0; }
.mini { font: inherit; font-size: 13px; font-weight: 700; padding: 5px 11px; border-radius: 8px; border: 1px solid var(--line); background: #fff; cursor: pointer; color: var(--text); }
.mini:hover { border-color: var(--red); color: var(--red); }
.mini.go { background: var(--red); border-color: var(--red); color: #fff; }
.mini.go:hover { background: var(--red-dark); color: #fff; }
.mini.bad { color: #a31212; }

.pager { display: flex; justify-content: center; align-items: center; gap: 18px; padding-top: 16px; color: var(--muted); }

@media (max-width: 900px) {
  .form-grid { grid-template-columns: 1fr; }
  .form-grid .span-3 { grid-column: auto; }
}
</style>
@endpush

@section('content')
@php
  $me      = auth('admin')->user();
  $isEdit  = (bool) $edit;
  $labels  = \App\Models\Reservation\Reservation::STATUS_LABELS;
  $actions = \App\Models\Reservation\Reservation::ACTION_LABELS;
  $keep    = array_filter(['q' => $search, 'when' => $when !== 'upcoming' ? $when : null]);
  $whenLabels = ['upcoming' => 'Upcoming', 'today' => 'Today', 'past' => 'Past', 'all' => 'All dates'];
@endphp

@if ($errors->any())
  <div class="flash error">{{ $errors->first() }}</div>
@endif

//Add / Edit form
@if ($me->can('reservations.manage'))
<div class="panel form-panel">
  <div class="panel-head">
    <h2>{{ $isEdit ? 'Edit reservation' : 'Add reservation' }}</h2>
    @if ($isEdit) <a href="{{ route('admin.reservations') }}">Cancel edit</a> @endif
  </div>

  <form method="post" action="{{ $isEdit ? route('admin.reservations.update', $edit) : route('admin.reservations.store') }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="form-grid">
      <div class="field">
        <label for="guest_name">Guest name</label>
        <input type="text" id="guest_name" name="guest_name" required maxlength="120"
               value="{{ old('guest_name', $edit->guest_name ?? '') }}">
      </div>

      <div class="field">
        <label for="guest_phone">Phone</label>
        <input type="text" id="guest_phone" name="guest_phone" required maxlength="30"
               value="{{ old('guest_phone', $edit->guest_phone ?? '') }}">
      </div>

      <div class="field">
        <label for="party_size">Guests</label>
        <input type="number" id="party_size" name="party_size" min="1" max="100" required
               value="{{ old('party_size', $edit->party_size ?? 2) }}">
      </div>

      <div class="field">
        <label for="reservation_at">Date &amp; time</label>
        <input type="datetime-local" id="reservation_at" name="reservation_at" required
               value="{{ old('reservation_at', $isEdit ? $edit->reservation_at->format('Y-m-d\TH:i') : '') }}">
      </div>

      <div class="field">
        <label for="table_id">Table (optional)</label>
        <select id="table_id" name="table_id">
          <option value="">Not assigned yet</option>
          @foreach ($tables as $t)
            <option value="{{ $t->id }}"
              {{ (int) old('table_id', $edit->table_id ?? 0) === $t->id ? 'selected' : '' }}>
              {{ $t->table_number }} ({{ $t->capacity }} seats)
            </option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label for="notes">Notes (optional)</label>
        <input type="text" id="notes" name="notes" maxlength="255"
               value="{{ old('notes', $edit->notes ?? '') }}" placeholder="Birthday, high chair...">
      </div>
    </div>

    <button class="btn" type="submit">{{ $isEdit ? 'Save changes' : 'Add reservation' }}</button>
  </form>
</div>
@endif

//Status tabs
<div class="tabs">
  <a href="{{ route('admin.reservations', $keep) }}" class="{{ $status === '' ? 'current' : '' }}">
    All <span>{{ $counts->sum() }}</span>
  </a>
  @foreach ($labels as $key => $label)
    <a href="{{ route('admin.reservations', array_merge($keep, ['status' => $key])) }}"
       class="{{ $status === $key ? 'current' : '' }}">
      {{ $label }} <span>{{ $counts[$key] ?? 0 }}</span>
    </a>
  @endforeach
</div>

<div class="panel">
  <div class="panel-head">
    <h2>{{ $reservations->total() }} {{ \Illuminate\Support\Str::plural('reservation', $reservations->total()) }}</h2>
  </div>

  <form method="get" class="filter-row">
    @if ($status !== '') <input type="hidden" name="status" value="{{ $status }}"> @endif

    <input type="search" name="q" value="{{ $search }}" placeholder="Search guest name or phone">

    <select name="when">
      @foreach ($whenLabels as $key => $label)
        <option value="{{ $key }}" {{ $when === $key ? 'selected' : '' }}>{{ $label }}</option>
      @endforeach
    </select>

    <button class="btn btn-sm" type="submit">Filter</button>
    @if ($search !== '' || $status !== '' || $when !== 'upcoming')
      <a class="btn btn-ghost btn-sm" href="{{ route('admin.reservations') }}">Clear</a>
    @endif
  </form>

  @if ($reservations->isNotEmpty())
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>When</th><th>Guest</th><th>Guests</th><th>Table</th>
            <th>Status</th><th>Notes</th><th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($reservations as $r)
            <tr class="{{ $r->reservation_at->isToday() ? 'is-today' : '' }}">
              <td>
                <strong>{{ $r->reservation_at->format('d M Y') }}</strong>
                <div class="muted">{{ $r->reservation_at->format('h:i A') }}</div>
              </td>
              <td>
                {{ $r->guest_name }}
                <div class="muted"><a href="tel:{{ $r->guest_phone }}">{{ $r->guest_phone }}</a></div>
              </td>
              <td>{{ $r->party_size }}</td>
              <td>{{ $r->table->table_number ?? '-' }}</td>
              <td><span class="badge {{ $r->status }}">{{ $labels[$r->status] ?? $r->status }}</span></td>
              <td class="notes">{{ $r->notes ?: '-' }}</td>
              <td>
                <div class="row-actions">
                  @if ($me->can('reservations.status'))
                    @foreach ($r->nextStatuses() as $s)
                      <form method="post" action="{{ route('admin.reservations.status', $r) }}"
                            @if (in_array($s, ['cancelled', 'no_show'])) onsubmit="return confirm('{{ $actions[$s] }} this reservation?')" @endif>
                        @csrf
                        <button type="submit" name="status" value="{{ $s }}"
                                class="mini {{ in_array($s, ['confirmed', 'seated', 'completed']) ? 'go' : 'bad' }}">
                          {{ $actions[$s] }}
                        </button>
                      </form>
                    @endforeach
                  @endif

                  @if ($me->can('reservations.manage') && $r->isEditable())
                    <a href="{{ route('admin.reservations.edit', $r) }}">Edit</a>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if ($reservations->hasPages())
      <div class="pager">
        @if ($reservations->onFirstPage())
          <span>&larr; Previous</span>
        @else
          <a href="{{ $reservations->previousPageUrl() }}">&larr; Previous</a>
        @endif

        <span>Page {{ $reservations->currentPage() }} of {{ $reservations->lastPage() }}</span>

        @if ($reservations->hasMorePages())
          <a href="{{ $reservations->nextPageUrl() }}">Next &rarr;</a>
        @else
          <span>Next &rarr;</span>
        @endif
      </div>
    @endif
  @else
    <p class="empty">No reservations found for this filter.</p>
  @endif
</div>
@endsection