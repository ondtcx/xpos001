<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseItem extends Model
{
    public const LINE_TYPE_NORMAL = 'normal';

    public const LINE_TYPE_BONUS = 'bonus';

    protected $fillable = [
        'purchase_id',
        'variant_id',
        'line_type',
        'quantity',
        'bonus_quantity',
        'unit_cost_base_amount',
        'line_subtotal_amount',
        'line_discount_amount',
        'tax_iva_amount',
        'tax_ice_amount',
        'tax_vat_amount',
        'tax_fixed_amount',
        'tax_other_amount',
        'allocated_global_discount_amount',
        'allocated_global_tax_iva_amount',
        'allocated_global_tax_ice_amount',
        'allocated_global_tax_other_amount',
        'allocated_extra_costs_amount',
        'gift_quantity',
        'unit_cost_final_amount',
        'total_cost_amount',
        'expiration_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'bonus_quantity' => 'decimal:3',
        'gift_quantity' => 'decimal:3',
        'expiration_date' => 'date',
    ];

    public function isBonusLine(): bool
    {
        return $this->line_type === self::LINE_TYPE_BONUS;
    }

    public function isNormalLine(): bool
    {
        return ! $this->isBonusLine();
    }

    public function receivedQuantity(): string
    {
        return number_format((float) $this->quantity + (float) $this->bonus_quantity, 3, '.', '');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(InventoryLot::class);
    }
}
