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
        return $this->page($request);
    }

    public function edit(Request $request, AdminUser $user)
    {
        $this->guardTarget($user);

        return $this->page($request, $user);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', 'max:150', 'unique:admin_users,email'],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role_id'   => ['required', Rule::in($this->allowedRoles()->pluck('id')->all())],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = AdminUser::create([
            'full_name'     => $data['full_name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'is_active'     => true, // block a user later with the Active/Inactive toggle
        ]);

        $user->syncRoles([Role::findOrFail($data['role_id'])->name]);

        return redirect()->route('admin.users')->with('ok', 'Admin user created.');
    }

    public function update(Request $request, AdminUser $user)
    {
        $this->guardTarget($user);

        $isSelf = $user->id === Auth::guard('admin')->id();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', 'max:150', Rule::unique('admin_users', 'email')->ignore($user->id)],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role_id'   => ['required', Rule::in($this->allowedRoles()->pluck('id')->all())],
            'password'  => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $payload = [
            'full_name' => $data['full_name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
        ];

        if (! empty($data['password'])) {
            $payload['password_hash'] = Hash::make($data['password']);
        }

        $user->update($payload);

        
        if (! $isSelf) {
            $user->syncRoles([Role::findOrFail($data['role_id'])->name]);
        }

        return redirect()->route('admin.users')->with('ok', 'Admin user updated.');
    }

    public function toggle(AdminUser $user)
    {
        $this->guardTarget($user);

        if ($user->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('ok', $user->is_active ? 'User activated.' : 'User deactivated.');
    }

    public function destroy(AdminUser $user)
    {
        $this->guardTarget($user);

        if ($user->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'Super admin cannot be deleted.');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('ok', 'Admin user deleted.');
    }

    

    private function page(Request $request, ?AdminUser $editUser = null)
    {
        /** @var AdminUser $me */
        $me     = Auth::guard('admin')->user();
        $search = trim((string) $request->query('q', ''));

        $users = AdminUser::with('roles')
            // only a super admin can see super admin accounts
            ->when(! $me->hasRole('super_admin'), function ($q) {
                $q->whereDoesntHave('roles', fn ($r) => $r->where('name', 'super_admin'));
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('full_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->get();

        return view('admin.users', [
            'users'    => $users,
            'roles'    => $this->allowedRoles(),
            'editUser' => $editUser,
            'search'   => $search,
            'meId'     => $me->id,
        ]);
    }

   
    private function allowedRoles()
    {
        /** @var AdminUser $me */
        $me = Auth::guard('admin')->user();

        return Role::where('guard_name', 'admin')
            ->when(! $me->hasRole('super_admin'), fn ($q) => $q->where('name', '<>', 'super_admin'))
            ->orderBy('name')
            ->get();
    }

    // only a super admin can touch a super admin account
    private function guardTarget(AdminUser $user): void
    {
        /** @var AdminUser $me */
        $me = Auth::guard('admin')->user();

        if ($user->hasRole('super_admin') && ! $me->hasRole('super_admin')) {
            abort(403, 'Only a super admin can manage a super admin account.');
        }
    }
}