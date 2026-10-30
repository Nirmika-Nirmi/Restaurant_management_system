<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Config\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $users = AdminUser::with('roles')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('full_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->get();

        $roles = Role::where('guard_name', 'admin')->orderBy('name')->get();

        return view('Admin.users', [
            'users'    => $users,
            'roles'    => $roles,
            'search'   => $search,
            'editUser' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', 'max:150', 'unique:admin_users,email'],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role_id'   => ['required', 'exists:roles,id'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = AdminUser::create([
            'full_name'     => $data['full_name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'is_active'     => $request->boolean('is_active'),
        ]);

        $role = Role::findOrFail($data['role_id']);
        $user->syncRoles([$role->name]);

        return redirect()
            ->route('admin.users')
            ->with('ok', 'User created successfully.');
    }

    public function edit(AdminUser $user)
    {
        $users = AdminUser::with('roles')->orderBy('full_name')->get();
        $roles = Role::where('guard_name', 'admin')->orderBy('name')->get();

        return view('Admin.users', [
            'users'    => $users,
            'roles'    => $roles,
            'search'   => '',
            'editUser' => $user,
        ]);
    }

    public function update(Request $request, AdminUser $user)
    {
        $me     = Auth::guard('admin')->user();
        $isSelf = $user->id === $me->id;

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email'     => [
                'required', 'email', 'max:150',
                Rule::unique('admin_users', 'email')->ignore($user->id),
            ],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role_id'   => ['required', 'exists:roles,id'],
            'password'  => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $payload = [
            'full_name' => $data['full_name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'is_active' => $isSelf ? true : $request->boolean('is_active'),
        ];

        if (! empty($data['password'])) {
            $payload['password_hash'] = Hash::make($data['password']);
        }

        $user->update($payload);

        if (! $isSelf) {
            $role = Role::findOrFail($data['role_id']);
            $user->syncRoles([$role->name]);
        }

        return redirect()
            ->route('admin.users')
            ->with('ok', 'User updated successfully.');
    }

    public function toggle(AdminUser $user)
    {
        if ($user->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('ok', $user->is_active ? 'User activated.' : 'User deactivated.');
    }

    public function destroy(AdminUser $user)
    {
        if ($user->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'Super admin cannot be deleted.');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('ok', 'User deleted.');
    }
}