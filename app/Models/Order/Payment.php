<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    const UPDATED_AT = null;

    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'method',
        'provider',
        'transaction_ref',
        'amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];
}