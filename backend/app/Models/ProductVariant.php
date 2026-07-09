<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * FEFO: pick the lot with the nearest non-null expiration_date that
     * still has available quantity. Tie-break by id ASC (oldest received
     * first when two lots share the same expiration).
     */
    public function nearestLot(): HasOne
    {
        return $this->hasOne(InventoryLot::class, 'variant_id')
            ->where('available_quantity', '>', 0)
            ->whereNotNull('expiration_date')
            ->orderBy('expiration_date', 'asc')
            ->orderBy('id', 'asc');
    }
}
