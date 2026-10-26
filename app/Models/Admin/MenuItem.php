<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $table = 'menu_items';

    protected $fillable = [
        'category_id',
        'name',
        'name_chinese',
        'slug',
        'description',
        'base_price',
        'image_url',
        'is_veg',
        'spice_level',
        'prep_time_min',
        'is_featured',
        'is_popular',
        'is_available',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'base_price'   => 'decimal:2',
        'is_veg'       => 'boolean',
        'is_featured'  => 'boolean',
        'is_popular'   => 'boolean',
        'is_available' => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}