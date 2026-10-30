@extends('admin.layouts.app')

@section('title', 'Admin users')

@push('styles')
<style>
.panel-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
.panel-head h2 { font-size: 20px; }
.form-panel { border-color: var(--red); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0 16px; }
.field { margin-bottom: 14px; }
.field label { display: block; font-weight: 500; margin-bottom: 6px; }
.hint { color: var(--muted); font-size: 13px; margin-top: 4px; }
input[type=text], input[type=email], input[type=password], input[type=search], select {
  width: 100%; font: inherit; padding: 10px 12px; border: 1px solid var(--line);
  border-radius: 8px; background: #fff; color: var(--text);
}
input:focus, select:focus { border-color: var(--red); outline: 0; box-shadow: 0 0 0 3px rgba(200,0,10,.15); }
input[type=checkbox] { width: 17px; height: 17px; accent-color: var(--red); }
.check-row { display: flex; align-items: center; gap: 8px; padding-top: 30px; }
.check-row label { margin: 0; font-weight: 400; }

.search-row { display: flex; gap: 8px; margin-bottom: 14px; max-width: 420px; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; vertical-align: middle; }
th { font-size: 13px; color: var(--muted); font-weight: 500; }
tr:last-child td { border-bottom: 0; }
.muted { color: var(--muted); font-size: 13px; }
.empty { color: var(--muted); padding: 18px 0; }

.avatar { display: inline-grid; place-items: center; width: 36px; height: 36px; border-radius: 50%; background: #fbe9ea; color: var(--red); font-weight: 700; margin-right: 10px; vertical-align: middle; }
.role-chip { display: inline-block; padding: 3px 10px; border-radius: 99px; background: #fbe9ea; color: var(--red); font-size: 13px; font-weight: 700; text-decoration: none; }
.role-chip:hover { background: var(--red); color: #fff; }
.role-chip.super { background: var(--maroon); color: var(--gold); }
.you { display: inline-block; padding: 2px 8px; border-radius: 99px; background: #dcebfa; color: #1d5fa8; font-size: 12px; margin-left: 6px; }

.perm-count { display: inline-flex; align-items: center; gap: 8px; }
.meter { width: 70px; height: 6px; border-radius: 99px; background: var(--line); overflow: hidden; }
.meter span { display: block; height: 100%; background: var(--red); }
.meter.full span { background: var(--gold); }

.toggle { font: inherit; font-size: 13px; font-weight: 700; padding: 3px 12px; border-radius: 99px; border: 1px solid var(--line); background: #eee; color: var(--muted); cursor: pointer; }
.toggle.on { background: #d9f1e4; color: var(--ok); border-color: #b7e0c8; }
.row-actions { display: flex; gap: 14px; align-items: center; }
.row-actions form { margin: 0; }
.danger { color: #a31212; background: none; border: 0; padding: 0; font: inherit; cursor: pointer; text-decoration: underline; }

.flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; }
.flash.ok { background: #d9f1e4; color: var(--ok); }
.flash.error { background: #fbdcdc; color: #8a1010; }

@media (max-width: 900px) {
  .form-grid { grid-template-columns: 1fr; }
  .check-row { padding-top: 0; }
}
</style>
@endpush

@section('content')
@php
  $isEdit = (bool) $editUser;
  $me     = auth('admin')->user();
@endphp

// Flash messages
@if (session('ok'))
  <div class="flash ok">{{ session('ok') }}</div>
@endif
@if (session('error'))
  <div class="flash error">{{ session('error') }}</div>
@endif
@if ($errors->any())
  <div class="flash error">{{ $errors->first() }}</div>
@endif

// Add / Edit user
<div class="panel form-panel">
  <div class="panel-head">
    <h2>{{ $isEdit ? 'Edit admin user' : 'Add admin user' }}</h2>
    @if ($isEdit)
      <a href="{{ route('admin.users') }}">Cancel edit</a>
    @endif
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
        <select id="role_id" name="role_id" required>
          <option value="">Select role</option>
          @foreach ($roles as $role)
            <option value="{{ $role->id }}"
              {{ (int) old('role_id', $isEdit ? $editUser->roles->first()?->id : 0) === $role->id ? 'selected' : '' }}>
              {{ ucwords(str_replace('_', ' ', $role->name)) }}
            </option>
          @endforeach
        </select>
        <div class="hint">Access is decided by the role. Change it on the Roles &amp; permissions page.</div>
      </div>

      <div class="field">
        <label for="password">
          {{ $isEdit ? 'New password (leave empty to keep)' : 'Password' }}
        </label>
        <input type="password" id="password" name="password" minlength="8"
               autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
      </div>

      <div class="field">
        <label for="password_confirmation">Confirm password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" minlength="8"
               autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
      </div>

      <div class="check-row">
        <input type="checkbox" id="is_active" name="is_active" value="1"
               {{ old('is_active', $editUser->is_active ?? true) ? 'checked' : '' }}>
        <label for="is_active">Active (can log in)</label>
      </div>
    </div>

    <button class="btn" type="submit">{{ $isEdit ? 'Save changes' : 'Create user' }}</button>
  </form>
</div>

//Users list 
<div class="panel">
  <div class="panel-head"><h2>{{ $users->count() }} admin users</h2></div>

  <form method="get" class="search-row">
    <input type="search" name="q" value="{{ $search }}" placeholder="Search by name, email or phone">
    <button class="btn btn-ghost btn-sm" type="submit">Search</button>
  </form>

  @if ($users->isNotEmpty())
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>User</th><th>Phone</th><th>Role</th>
            <th>Last login</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($users as $u)
            @php $roleName = $u->roles->first()?->name; @endphp
            <tr>
              <td>
                <span class="avatar">{{ strtoupper(mb_substr($u->full_name, 0, 1)) }}</span>
                <strong>{{ $u->full_name }}</strong>
                @if ($u->id === $me->id) <span class="you">You</span> @endif
                <div class="muted" style="margin-left:46px">{{ $u->email }}</div>
              </td>
              <td>{{ $u->phone ?: '-' }}</td>
              <td>
                <span class="role-chip {{ $roleName === 'super_admin' ? 'super' : '' }}">
                  {{ $roleName ? ucwords(str_replace('_', ' ', $roleName)) : 'No role' }}
                </span>
              </td>
              <td class="muted">
                {{ $u->last_login_at ? $u->last_login_at->format('d M Y, h:i A') : 'Never' }}
              </td>
              <td>
                @if ($u->id !== $me->id)
                  <form method="post" action="{{ route('admin.users.toggle', $u) }}" style="margin:0">
                    @csrf
                    <button type="submit" class="toggle {{ $u->is_active ? 'on' : '' }}">
                      {{ $u->is_active ? 'Active' : 'Inactive' }}
                    </button>
                  </form>
                @else
                  <span class="toggle {{ $u->is_active ? 'on' : '' }}">Active</span>
                @endif
              </td>
              <td>
                <div class="row-actions">
                  <a href="{{ route('admin.users.edit', $u) }}">Edit</a>
                  @if ($u->id !== $me->id && $roleName !== 'super_admin')
                    <form method="post" action="{{ route('admin.users.destroy', $u) }}"
                          onsubmit="return confirm('Delete {{ addslashes($u->full_name) }}?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="danger">Delete</button>
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
    <p class="empty">No users found.</p>
  @endif
</div>
@endsection