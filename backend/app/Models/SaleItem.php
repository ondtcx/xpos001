<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'item_type',
        'sale_presentation_id',
        'variant_id',
        'description_snapshot',
        'quantity',
        'unit_price_amount',
        'original_unit_price_amount',
        'manual_unit_price_amount',
        'has_manual_price_override',
        'manual_price_reason',
        'subtotal_amount',
        'total_cost_amount',
        'total_profit_amount',
        'has_cost_warning',
        'has_stock_warning',
        'stock_warning_acknowledged',
        'cost_warning_acknowledged',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'has_cost_warning' => 'boolean',
        'has_manual_price_override' => 'boolean',
        'has_stock_warning' => 'boolean',
        'stock_warning_acknowledged' => 'boolean',
        'cost_warning_acknowledged' => 'boolean',
    ];

    public function hasManualPriceOverride(): bool
    {
        return $this->has_manual_price_override;
    }

    public function effectiveUnitPriceAmount(): int
    {
        return $this->manual_unit_price_amount ?? $this->unit_price_amount;
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(SalePresentation::class, 'sale_presentation_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function lotConsumptions(): HasMany
    {
        return $this->hasMany(SaleItemLotConsumption::class);
    }
}
