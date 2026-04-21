<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'base_unit_id',
        'tracks_expiration',
        'is_returnable',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'tracks_expiration' => 'boolean',
        'is_returnable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(BaseUnit::class);
    }

    public function presentations(): HasMany
    {
        return $this->hasMany(SalePresentation::class);
    }
}
