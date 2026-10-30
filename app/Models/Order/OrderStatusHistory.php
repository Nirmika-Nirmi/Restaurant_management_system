<?php

namespace App\Models\Order;

use App\Models\Admin\Config\AdminUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    // only created_at exists in order_status_history
    const UPDATED_AT = null;

    protected $table = 'order_status_history';

    protected $fillable = [
        'order_id',
        'status',
        'changed_by',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'changed_by');
    }
}