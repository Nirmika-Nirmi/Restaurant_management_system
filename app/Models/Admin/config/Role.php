<?php

namespace App\Models\Admin\Config;

use App\Models\Admin\config\AdminUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = ['name', 'description'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        );
    }

    public function adminUsers(): HasMany
    {
        return $this->hasMany(AdminUser::class, 'role_id');
    }

    public function hasPermission(string $code): bool
    {
        return $this->permissions()
                    ->where('permissions.code', $code)
                    ->exists();
    }

    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === 'super_admin';
    }
}