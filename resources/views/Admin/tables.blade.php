@extends('admin.layouts.app')

@section('title', 'Tables')

@push('styles')
<style>
.panel-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
.panel-head h2 { font-size: 20px; }
.panel-head a { font-size: 14px; }
.form-panel { border-color: var(--red); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; max-width: 520px; }
.field { margin-bottom: 14px; }
.field label { display: block; font-weight: 500; margin-bottom: 6px; }
input[type=text], input[type=number], select {
  width: 100%; font: inherit; padding: 10px 12px; border: 1px solid var(--line);
  border-radius: 8px; background: #fff; color: var(--text);
}
input:focus, select:focus { border-color: var(--red); outline: 0; box-shadow: 0 0 0 3px rgba(200,0,10,.15); }

.summary { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; }
.chip { padding: 6px 14px; border-radius: 99px; font-size: 14px; font-weight: 500; background: #eee; color: #444; }
.chip.available { background: #d9f1e4; color: var(--ok); }
.chip.occupied { background: #fbdcdc; color: #a31212; }
.chip.reserved { background: #dcebfa; color: #1d5fa8; }
.chip.cleaning { background: #fff1d6; color: #a86400; }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; vertical-align: middle; }
th { font-size: 13px; color: var(--muted); font-weight: 500; }
tr:last-child td { border-bottom: 0; }
tr.is-off td { opacity: .5; }
.muted { color: var(--muted); font-size: 13px; }
.empty { color: var(--muted); padding: 18px 0; }

.status-form { margin: 0; }
.status-form select { width: auto; padding: 6px 10px; }

.toggle { font: inherit; font-size: 13px; font-weight: 700; padding: 3px 12px; border-radius: 99px; border: 1px solid var(--line); background: #eee; color: var(--muted); cursor: pointer; }
.toggle.on { background: #d9f1e4; color: var(--ok); border-color: #b7e0c8; }
.row-actions { display: flex; gap: 14px; align-items: center; }
.row-actions form { margin: 0; }

@media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
@php
  $me     = auth('admin')->user();
  $isEdit = (bool) $edit;
  $labels = \App\Models\Reservation\RestaurantTable::STATUS_LABELS;
  $byStatus = $tables->where('is_active', true)->groupBy('status')->map->count();
@endphp

@if ($errors->any())
  <div class="flash error">{{ $errors->first() }}</div>
@endif

@if ($me->can('reservations.manage'))
<div class="panel form-panel">
  <div class="panel-head">
    <h2>{{ $isEdit ? 'Edit table' : 'Add table' }}</h2>
    @if ($isEdit) <a href="{{ route('admin.tables') }}">Cancel edit</a> @endif
  </div>

  <form method="post" action="{{ $isEdit ? route('admin.tables.update', $edit) : route('admin.tables.store') }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="form-grid">
      <div class="field">
        <label for="table_number">Table number</label>
        <input type="text" id="table_number" name="table_number" maxlength="20" required
               value="{{ old('table_number', $edit->table_number ?? '') }}" placeholder="T1">
      </div>

      <div class="field">
        <label for="capacity">Seats</label>
        <input type="number" id="capacity" name="capacity" min="1" max="100" required
               value="{{ old('capacity', $edit->capacity ?? 4) }}">
      </div>
    </div>

    <button class="btn" type="submit">{{ $isEdit ? 'Save changes' : 'Add table' }}</button>
  </form>
</div>
@endif

<div class="panel">
  <div class="panel-head"><h2>{{ $tables->count() }} tables</h2></div>

  <div class="summary">
    @foreach ($labels as $key => $label)
      <span class="chip {{ $key }}">{{ $label }}: {{ $byStatus[$key] ?? 0 }}</span>
    @endforeach
  </div>

  @if ($tables->isNotEmpty())
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Table</th><th>Seats</th><th>Status</th><th>Next booking</th><th>Active</th><th></th></tr>
        </thead>
        <tbody>
          @foreach ($tables as $t)
            <tr class="{{ $t->is_active ? '' : 'is-off' }}">
              <td><strong>{{ $t->table_number }}</strong></td>
              <td>{{ $t->capacity }}</td>
              <td>
                @if ($me->can('reservations.status'))
                  <form class="status-form" method="post" action="{{ route('admin.tables.status', $t) }}">
                    @csrf
                    <select name="status" onchange="this.form.submit()">
                      @foreach ($labels as $key => $label)
                        <option value="{{ $key }}" {{ $t->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                      @endforeach
                    </select>
                  </form>
                @else
                  <span class="chip {{ $t->status }}">{{ $labels[$t->status] ?? $t->status }}</span>
                @endif
              </td>
              <td class="muted">
                {{ $t->next_booking ? \Illuminate\Support\Carbon::parse($t->next_booking)->format('d M, h:i A') : '-' }}
              </td>
              <td>
                @if ($me->can('reservations.manage'))
                  <form method="post" action="{{ route('admin.tables.toggle', $t) }}" style="margin:0">
                    @csrf
                    <button type="submit" class="toggle {{ $t->is_active ? 'on' : '' }}">
                      {{ $t->is_active ? 'Active' : 'Inactive' }}
                    </button>
                  </form>
                @else
                  {{ $t->is_active ? 'Active' : 'Inactive' }}
                @endif
              </td>
              <td>
                <div class="row-actions">
                  @if ($me->can('reservations.manage'))
                    <a href="{{ route('admin.tables', ['edit' => $t->id]) }}">Edit</a>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <p class="empty">No tables yet. Add your first table above.</p>
  @endif
</div>
@endsection