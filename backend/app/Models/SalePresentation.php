<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class SalePresentation extends Model
{
    protected $fillable = [
        'product_variant_id',
        'name',
        'conversion_factor',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:3',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(SalePrice::class);
    }
}
