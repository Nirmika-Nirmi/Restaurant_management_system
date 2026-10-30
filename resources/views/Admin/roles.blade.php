@extends('admin.layouts.app')

@section('title', 'Roles & permissions')

@push('styles')
<style>
.panel-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
.panel-head h2 { font-size: 20px; }
.role-layout { display: grid; grid-template-columns: 290px 1fr; gap: 20px; align-items: start; }

.role-list { list-style: none; margin: 0 0 18px; padding: 0; }
.role-list li a { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 8px; text-decoration: none; color: var(--text); }
.role-list li a:hover { background: var(--bg); }
.role-list li.current a { background: #fbe9ea; color: var(--red); font-weight: 700; }
.role-list .meta { font-size: 12px; color: var(--muted); font-weight: 400; text-align: right; }
.lock { font-size: 11px; padding: 2px 8px; border-radius: 99px; background: var(--maroon); color: var(--gold); margin-left: 6px; }

.field { margin-bottom: 14px; }
.field label { display: block; font-weight: 500; margin-bottom: 6px; }
input[type=text] { width: 100%; font: inherit; padding: 10px 12px; border: 1px solid var(--line); border-radius: 8px; background: #fff; }
input[type=text]:focus { border-color: var(--red); outline: 0; box-shadow: 0 0 0 3px rgba(200,0,10,.15); }
input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--red); cursor: pointer; }
input[type=checkbox]:disabled { cursor: not-allowed; }

.role-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
.role-head h2 { font-size: 22px; }
.role-head .muted { color: var(--muted); font-size: 14px; }
.counter strong { font-family: 'Playfair Display', serif; font-size: 28px; color: var(--red); }

.access { margin-bottom: 18px; padding: 12px 14px; background: var(--maroon); border-radius: 10px; }
.access .lbl { color: rgba(255,255,255,.6); font-size: 12px; margin-bottom: 8px; }
.access .chips { display: flex; flex-wrap: wrap; gap: 8px; }
.access .chip { padding: 5px 12px; border-radius: 99px; font-size: 13px; background: rgba(255,255,255,.08); color: rgba(255,255,255,.3); transition: .15s; }
.access .chip.on { background: var(--gold); color: var(--maroon); font-weight: 700; }

.presets { display: flex; gap: 8px; margin-bottom: 14px; }
.chip-btn { font: inherit; font-size: 14px; padding: 7px 14px; border-radius: 99px; border: 1px solid var(--line); background: #fff; cursor: pointer; }
.chip-btn:hover { border-color: var(--red); color: var(--red); }
.chip-btn:disabled { opacity: .5; cursor: not-allowed; }

.table-wrap { overflow-x: auto; }
.perm-table { width: 100%; border-collapse: collapse; min-width: 640px; }
.perm-table th, .perm-table td { padding: 13px 10px; border-bottom: 1px solid var(--line); text-align: center; }
.perm-table thead th { font-size: 13px; color: var(--muted); font-weight: 500; }
.perm-table tbody tr:last-child td { border-bottom: 0; }
.perm-table .mod { text-align: left; font-weight: 700; }
.perm-table td.all { border-right: 1px solid var(--line); }
.perm-table tbody tr:hover { background: #fafafb; }
.perm-table .na { color: #c9c6c7; }

.note { background: #fff1d6; color: #7a4a00; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; }
.save-bar { display: flex; align-items: center; gap: 14px; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--line); }
.save-bar .muted { color: var(--muted); font-size: 13px; }
.danger-link { background: none; border: 0; padding: 0; font: inherit; font-size: 13px; color: #a31212; cursor: pointer; text-decoration: underline; }

.flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; }
.flash.ok { background: #d9f1e4; color: var(--ok); }
.flash.error { background: #fbdcdc; color: #8a1010; }

@media (max-width: 900px) { .role-layout { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
@php
  $locked     = $role['super'] ?? false;
  $totalPerms = collect($modules)->sum(fn ($m) => count($m['actions']));
@endphp

{{-- Flash messages --}}
@if (session('ok'))
  <div class="flash ok">{{ session('ok') }}</div>
@endif
@if (session('error'))
  <div class="flash error">{{ session('error') }}</div>
@endif
@if ($errors->any())
  <div class="flash error">{{ $errors->first() }}</div>
@endif

<div class="role-layout">

  {{-- ============ Roles list ============ --}}
  <aside>
    <div class="panel">
      <div class="panel-head"><h2>Roles</h2></div>

      <ul class="role-list">
        @foreach ($roles as $rid => $r)
          <li class="{{ $selected === $rid ? 'current' : '' }}">
            <a href="{{ route('admin.roles', ['role' => $rid]) }}">
              <span>
                {{ $r['name'] }}
                @if ($r['super']) <span class="lock">Full access</span> @endif
              </span>
              <span class="meta">{{ $r['users'] }} {{ $r['users'] === 1 ? 'user' : 'users' }}</span>
            </a>
          </li>
        @endforeach
      </ul>

      <form method="post" action="{{ route('admin.roles.store') }}">
        @csrf
        <div class="field">
          <label for="role_name">New role</label>
          <input type="text" id="role_name" name="name" placeholder="e.g. Kitchen Staff" maxlength="50" required>
        </div>
        <button class="btn btn-sm" type="submit">Create role</button>
      </form>
    </div>
  </aside>

  {{-- ============ Matrix ============ --}}
  <section>
    <div class="panel">
      @if ($role)
        <div class="role-head">
          <div>
            <h2>Permissions for: {{ $role['name'] }}</h2>
            <div class="muted">Every user with this role gets these permissions.</div>
          </div>
          <div class="counter">
            <strong id="selCount">{{ $locked ? $totalPerms : count($role['granted']) }}</strong> / {{ $totalPerms }}
          </div>
        </div>

        @if ($locked)
          <div class="note">Super admin always has full access to every page. This cannot be changed.</div>
        @endif

        {{-- sidebar preview --}}
        <div class="access">
          <div class="lbl">Sidebar pages this role will see</div>
          <div class="chips">
            @foreach ($sidebar as $label => $need)
              <span class="chip" data-needs="{{ $need }}">{{ $label }}</span>
            @endforeach
          </div>
        </div>

        <div class="presets">
          <button type="button" class="chip-btn" data-preset="all"  @disabled($locked)>Select all</button>
          <button type="button" class="chip-btn" data-preset="none" @disabled($locked)>Clear all</button>
        </div>

        <form method="post" action="{{ route('admin.roles.update', $selected) }}" id="permForm">
          @csrf
          @method('PUT')

          <div class="table-wrap">
            <table class="perm-table">
              <thead>
                <tr>
                  <th class="mod">Page</th>
                  <th>All</th>
                  @foreach ($columns as $label) <th>{{ $label }}</th> @endforeach
                </tr>
              </thead>
              <tbody>
                @foreach ($modules as $key => $mod)
                  @php
                    $rowCodes = array_map(fn ($a) => $key . '.' . $a, $mod['actions']);
                    $allOn    = $locked || count(array_intersect($rowCodes, $role['granted'])) === count($rowCodes);
                  @endphp
                  <tr>
                    <td class="mod">{{ $mod['label'] }}</td>
                    <td class="all">
                      <input type="checkbox" data-row-all="{{ $key }}" @checked($allOn) @disabled($locked)>
                    </td>
                    @foreach ($columns as $action => $label)
                      <td>
                        @if (in_array($action, $mod['actions'], true))
                          @php $code = $key . '.' . $action; @endphp
                          <input type="checkbox" name="permissions[]" value="{{ $code }}" data-row="{{ $key }}"
                                 @checked($locked || in_array($code, $role['granted'], true))
                                 @disabled($locked)>
                        @else
                          <span class="na">&ndash;</span>
                        @endif
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          @unless ($locked)
            <div class="save-bar">
              <button class="btn" type="submit">Save changes</button>
              <button class="btn btn-ghost" type="button" id="resetBtn">Reset</button>
              <span class="muted">Applies to all users with this role.</span>
            </div>
          @endunless
        </form>

        @unless ($locked)
          <form method="post" action="{{ route('admin.roles.destroy', $selected) }}"
                style="margin-top:14px"
                onsubmit="return confirm('Delete this role?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="danger-link">Delete this role</button>
          </form>
        @endunless
      @else
        <p style="color:var(--muted)">No roles found.</p>
      @endif
    </div>
  </section>

</div>
@endsection

@push('scripts')
<script>
  var locked  = {{ $locked ? 'true' : 'false' }};
  var total   = {{ $totalPerms }};

  var boxes   = Array.from(document.querySelectorAll('input[name="permissions[]"]'));
  var counter = document.getElementById('selCount');
  var initial = boxes.map(function (b) { return b.checked; });

  function refresh() {
    var checked = boxes.filter(function (b) { return b.checked; }).map(function (b) { return b.value; });

    if (counter) {
      counter.textContent = locked ? total : checked.length;
    }

    document.querySelectorAll('[data-row-all]').forEach(function (all) {
      var row = boxes.filter(function (b) { return b.dataset.row === all.dataset.rowAll; });
      all.checked = row.length > 0 && row.every(function (b) { return b.checked; });
    });

    document.querySelectorAll('.chip[data-needs]').forEach(function (c) {
      c.classList.toggle('on', locked || checked.indexOf(c.dataset.needs) !== -1);
    });
  }

  boxes.forEach(function (b) { b.addEventListener('change', refresh); });

  document.querySelectorAll('[data-row-all]').forEach(function (all) {
    all.addEventListener('change', function () {
      boxes.forEach(function (b) { if (b.dataset.row === all.dataset.rowAll) b.checked = all.checked; });
      refresh();
    });
  });

  document.querySelectorAll('[data-preset]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var on = btn.dataset.preset === 'all';
      boxes.forEach(function (b) { b.checked = on; });
      refresh();
    });
  });

  var reset = document.getElementById('resetBtn');
  if (reset) reset.addEventListener('click', function () {
    boxes.forEach(function (b, i) { b.checked = initial[i]; });
    refresh();
  });

  refresh();
</script>
@endpush