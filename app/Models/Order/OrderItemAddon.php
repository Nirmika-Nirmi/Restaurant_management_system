<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;

class OrderItemAddon extends Model
{
    protected $table = 'order_item_addons';

    public $timestamps = false;

    protected $casts = [
        'price' => 'decimal:2',
    ];
}