<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;    
use Spatie\Permission\Models\Role;          

class RoleController extends Controller
{
    private string $guard = 'admin';

    public function index(Request $request)
    {
        $modules = config('admin_permissions.modules');
        $columns = config('admin_permissions.columns');

        $list = Role::where('guard_name', $this->guard)
            ->with('permissions')
            ->withCount('users')
            ->orderByRaw("name = 'super_admin' desc")
            ->orderBy('name')
            ->get();

        $roles = [];
        foreach ($list as $r) {
            $roles[$r->id] = [
                'name'    => Str::headline($r->name),
                'users'   => $r->users_count,
                'super'   => $r->name === 'super_admin',
                'granted' => $r->permissions->pluck('name')->all(),
            ];
        }

        $selected = (int) $request->query('role', 0);
        if (! isset($roles[$selected])) {
            $selected = (int) array_key_first($roles);
        }

        $sidebar = [];
        foreach ($modules as $key => $mod) {
            $sidebar[$mod['label']] = $key . '.view';
        }

        return view('Admin.roles', [
            'roles'    => $roles,
            'selected' => $selected,
            'role'     => $roles[$selected] ?? null,
            'modules'  => $modules,
            'columns'  => $columns,
            'sidebar'  => $sidebar,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => Str::slug((string) $request->input('name'), '_'),
        ]);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('roles', 'name')->where('guard_name', $this->guard),
            ],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => $this->guard]);

        return redirect()
            ->route('admin.roles', ['role' => $role->id])
            ->with('ok', 'Role created. Now tick the pages this role can use.');
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'super_admin') {
            return back()->with('error', 'Super admin always has full access and cannot be changed.');
        }

        $allowed = [];
        foreach (config('admin_permissions.modules') as $module => $def) {
            foreach ($def['actions'] as $action) {
                $allowed[] = "{$module}.{$action}";
            }
        }

        $names = array_values(array_intersect((array) $request->input('permissions', []), $allowed));

        $role->syncPermissions(
            Permission::where('guard_name', $this->guard)
                ->whereIn('name', $names)
                ->get()
        );

        return redirect()
            ->route('admin.roles', ['role' => $role->id])
            ->with('ok', 'Permissions saved for ' . Str::headline($role->name) . '.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'super_admin') {
            return back()->with('error', 'Super admin role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Some admin users still have this role. Change their role first.');
        }

        $role->delete();

        return redirect()->route('admin.roles')->with('ok', 'Role deleted.');
    }
}