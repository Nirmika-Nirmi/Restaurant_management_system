@extends('admin.layouts.app')

@section('title', 'Admin users')

@push('styles')
<style>
.panel-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
.panel-head h2 { font-size: 20px; }
.panel-head a { font-size: 14px; }
.form-panel { border-color: var(--red); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
.field { margin-bottom: 14px; }
.field label { display: block; font-weight: 500; margin-bottom: 6px; }
input[type=text], input[type=email], input[type=password], input[type=search], select {
  width: 100%; font: inherit; padding: 10px 12px; border: 1px solid var(--line);
  border-radius: 8px; background: #fff; color: var(--text);
}
input:focus, select:focus { border-color: var(--red); outline: 0; box-shadow: 0 0 0 3px rgba(200,0,10,.15); }
input[type=checkbox] { width: 17px; height: 17px; accent-color: var(--red); }
.check-row { display: flex; align-items: center; gap: 8px; padding-top: 30px; }
.check-row label { margin: 0; font-weight: 400; }
.hint { color: var(--muted); font-size: 13px; margin-top: 4px; }

.search-row { display: flex; gap: 8px; margin-bottom: 14px; max-width: 420px; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 11px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; vertical-align: middle; }
th { font-size: 13px; color: var(--muted); font-weight: 500; }
tr:last-child td { border-bottom: 0; }
.empty { color: var(--muted); padding: 18px 0; }
.muted { color: var(--muted); font-size: 13px; }

.role-chip { display: inline-block; padding: 3px 10px; border-radius: 99px; background: #fbe9ea; color: var(--red); font-size: 13px; font-weight: 700; }
.you { display: inline-block; padding: 2px 8px; border-radius: 99px; background: #dcebfa; color: #1d5fa8; font-size: 12px; margin-left: 6px; }
.toggle { font: inherit; font-size: 13px; font-weight: 700; padding: 3px 12px; border-radius: 99px; border: 1px solid var(--line); background: #eee; color: var(--muted); cursor: pointer; }
.toggle.on { background: #d9f1e4; color: var(--ok); border-color: #b7e0c8; }
.row-actions { display: flex; gap: 14px; align-items: center; }
.row-actions form { margin: 0; }
.link-btn { background: none; border: 0; padding: 0; font: inherit; color: #a31212; cursor: pointer; text-decoration: underline; }

@media (max-width: 900px) {
  .form-grid { grid-template-columns: 1fr; }
  .check-row { padding-top: 0; }
}
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Str;
  $me       = auth('admin')->user();
  $isEdit   = (bool) $editUser;
  $isSelf   = $isEdit && $editUser->id === $meId;
  $currentRole = $isEdit ? $editUser->getRoleNames()->first() : null;
@endphp

@if ($errors->any())
  <div class="flash error">{{ $errors->first() }}</div>
@endif

{{-- ============ Add / Edit form ============ --}}
@if ($isEdit ? $me->can('users.edit') : $me->can('users.create'))
<div class="panel form-panel">
  <div class="panel-head">
    <h2>{{ $isEdit ? 'Edit admin user' : 'Add admin user' }}</h2>
    @if ($isEdit) <a href="{{ route('admin.users') }}">Cancel edit</a> @endif
  </div>

  <form method="post" action="{{ $isEdit ? route('admin.users.update', $editUser) : route('admin.users.store') }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="form-grid">
      <div class="field">
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" required maxlength="120"
               value="{{ old('full_name', $editUser->full_name ?? '') }}">
      </div>

      <div class="field">
        <label for="email">Email (login)</label>
        <input type="email" id="email" name="email" required maxlength="150"
               value="{{ old('email', $editUser->email ?? '') }}">
      </div>

      <div class="field">
        <label for="phone">Phone</label>
        <input type="text" id="phone" name="phone" maxlength="30"
               value="{{ old('phone', $editUser->phone ?? '') }}">
      </div>

      <div class="field">
        <label for="role_id">Role</label>
        @if ($isSelf)
          <input type="hidden" name="role_id" value="{{ $roles->firstWhere('name', $currentRole)?->id }}">
          <select id="role_id" disabled>
            @foreach ($roles as $r)
              <option {{ $r->name === $currentRole ? 'selected' : '' }}>{{ Str::headline($r->name) }}</option>
            @endforeach
          </select>
          <div class="hint">You cannot change your own role.</div>
        @else
          <select id="role_id" name="role_id" required>
            <option value="">Select role</option>
            @foreach ($roles as $r)
              <option value="{{ $r->id }}"
                {{ old('role_id', $roles->firstWhere('name', $currentRole)?->id) == $r->id ? 'selected' : '' }}>
                {{ Str::headline($r->name) }}
              </option>
            @endforeach
          </select>
        @endif
      </div>

      <div class="field">
        <label for="password">{{ $isEdit ? 'New password (leave empty to keep)' : 'Password' }}</label>
        <input type="password" id="password" name="password" minlength="8" autocomplete="new-password"
               {{ $isEdit ? '' : 'required' }}>
      </div>

      <div class="field">
        <label for="password_confirmation">Confirm password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" minlength="8"
               autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
      </div>

      @unless ($isSelf)
        <div class="check-row">
          <input type="checkbox" id="is_active" name="is_active" value="1"
                 {{ old('is_active', $editUser->is_active ?? true) ? 'checked' : '' }}>
          <label for="is_active">Active (can log in)</label>
        </div>
      @endunless
    </div>

    <button class="btn" type="submit">{{ $isEdit ? 'Save changes' : 'Create user' }}</button>
  </form>
</div>
@endif

{{-- ============ List ============ --}}
<div class="panel">
  <div class="panel-head">
    <h2>{{ $users->count() }} {{ Str::plural('admin user', $users->count()) }}</h2>
  </div>

  <form method="get" class="search-row">
    <input type="search" name="q" value="{{ $search }}" placeholder="Search by name, email or phone">
    <button class="btn btn-ghost btn-sm" type="submit">Search</button>
  </form>

  @if ($users->isNotEmpty())
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Last login</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          @foreach ($users as $u)
            <tr>
              <td>
                <strong>{{ $u->full_name }}</strong>
                @if ($u->id === $meId) <span class="you">You</span> @endif
              </td>
              <td>{{ $u->email }}</td>
              <td>{{ $u->phone ?: '-' }}</td>
              <td><span class="role-chip">{{ Str::headline($u->getRoleNames()->first() ?? '-') }}</span></td>
              <td class="muted">{{ $u->last_login_at ? $u->last_login_at->format('d M Y, h:i A') : 'Never' }}</td>
              <td>
                @if ($u->id !== $meId && $me->can('users.edit'))
                  <form method="post" action="{{ route('admin.users.toggle', $u) }}" style="margin:0">
                    @csrf
                    <button type="submit" class="toggle {{ $u->is_active ? 'on' : '' }}">
                      {{ $u->is_active ? 'Active' : 'Inactive' }}
                    </button>
                  </form>
                @else
                  <span class="toggle {{ $u->is_active ? 'on' : '' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span>
                @endif
              </td>
              <td>
                <div class="row-actions">
                  @if ($me->can('users.edit'))
                    <a href="{{ route('admin.users.edit', $u) }}">Edit</a>
                  @endif
                  @if ($u->id !== $meId && $me->can('users.delete'))
                    <form method="post" action="{{ route('admin.users.destroy', $u) }}"
                          onsubmit="return confirm('Delete {{ addslashes($u->full_name) }}?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="link-btn">Delete</button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <p class="empty">No admin users found.</p>
  @endif
</div>
@endsection