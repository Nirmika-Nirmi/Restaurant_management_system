<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $table = 'order_items';

    public $timestamps = false;

    protected $casts = [
        'unit_price'   => 'decimal:2',
        'line_total'   => 'decimal:2',
        'is_free_item' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(OrderItemAddon::class, 'order_item_id');
    }
}