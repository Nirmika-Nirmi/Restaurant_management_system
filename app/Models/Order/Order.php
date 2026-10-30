<?php

namespace App\Models\Order;

use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    // the orders table uses placed_at instead of created_at
    const CREATED_AT = 'placed_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'orders';

    protected $fillable = [
        'status',
        'payment_status',
        'cancel_reason',
        'estimated_minutes',
    ];

    protected $casts = [
        'placed_at'       => 'datetime',
        'updated_at'      => 'datetime',
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'delivery_fee'    => 'decimal:2',
        'total_amount'    => 'decimal:2',
    ];

    public const STATUS_LABELS = [
        'pending'          => 'Pending',
        'confirmed'        => 'Confirmed',
        'preparing'        => 'Preparing',
        'ready'            => 'Ready',
        'out_for_delivery' => 'Out for delivery',
        'completed'        => 'Completed',
        'cancelled'        => 'Cancelled',
    ];

    public const TYPE_LABELS = [
        'delivery' => 'Delivery',
        'pickup'   => 'Pickup',
        'dine_in'  => 'Dine-in',
    ];

    public const PAYMENT_LABELS = [
        'unpaid'   => 'Unpaid',
        'paid'     => 'Paid',
        'refunded' => 'Refunded',
        'failed'   => 'Failed',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id')->orderBy('id');
    }

    // the only status(es) this order can move to next
    public function nextStatuses(): array
    {
        return match ($this->status) {
            'pending'          => ['confirmed'],
            'confirmed'        => ['preparing'],
            'preparing'        => ['ready'],
            'ready'            => $this->order_type === 'delivery' ? ['out_for_delivery'] : ['completed'],
            'out_for_delivery' => ['completed'],
            default            => [],
        };
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'preparing', 'ready'], true);
    }
}