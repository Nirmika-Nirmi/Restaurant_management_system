<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password_hash',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // password column name is password_hash, not password
    public function getAuthPassword()
    {
        return (string) $this->password_hash;
    }

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    // customers table has no remember_token column
    public function getRememberTokenName()
    {
        return '';
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    public function isGuest(): bool
    {
        return $this->password_hash === null;
    }
}