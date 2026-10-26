<?php

namespace App\Models\Admin\Config;

use App\Models\Admin\Config\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'admin_users';

    protected $fillable = [
        'role_id',
        'full_name',
        'email',
        'phone',
        'password_hash',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // password column name is password_hash, not password
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    // admin_users has no remember_token column
    public function getRememberTokenName()
    {
        return '';
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasRole(string $name): bool
    {
        return $this->role && $this->role->name === $name;
    }

    public function hasPermission(string $code): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->role && $this->role->hasPermission($code);
    }
}