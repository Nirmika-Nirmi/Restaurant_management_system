<?php

namespace App\Models\Reservation;

use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    // the reservations table has only created_at
    const UPDATED_AT = null;

    protected $table = 'reservations';

    protected $fillable = [
        'customer_id',
        'guest_name',
        'guest_phone',
        'table_id',
        'party_size',
        'reservation_at',
        'notes',
        'status',
    ];

    protected $casts = [
        'reservation_at' => 'datetime',
        'created_at'     => 'datetime',
    ];

    public const STATUS_LABELS = [
        'pending'   => 'Pending',
        'confirmed' => 'Confirmed',
        'seated'    => 'Seated',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show'   => 'No show',
    ];

    // button text for each status change
    public const ACTION_LABELS = [
        'confirmed' => 'Confirm',
        'seated'    => 'Seat',
        'completed' => 'Complete',
        'no_show'   => 'No show',
        'cancelled' => 'Cancel',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function nextStatuses(): array
    {
        return match ($this->status) {
            'pending'   => ['confirmed', 'cancelled'],
            'confirmed' => ['seated', 'no_show', 'cancelled'],
            'seated'    => ['completed'],
            default     => [],
        };
    }

    // details can be edited only before the guests arrive
    public function isEditable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true);
    }
}