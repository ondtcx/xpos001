<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLot extends Model
{
    protected $fillable = [
        'variant_id',
        'purchase_item_id',
        'origin_type',
        'origin_id',
        'received_at',
        'expiration_date',
        'initial_quantity',
        'available_quantity',
        'bonus_quantity',
        'unit_cost_final_amount',
        'suggested_sale_price_amount',
        'is_estimated',
        'status',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'expiration_date' => 'date',
        'initial_quantity' => 'decimal:3',
        'available_quantity' => 'decimal:3',
        'bonus_quantity' => 'decimal:3',
        'is_estimated' => 'boolean',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'lot_id');
    }

    public function receivedQuantity(): string
    {
        return number_format((float) $this->initial_quantity, 3, '.', '');
    }
}
