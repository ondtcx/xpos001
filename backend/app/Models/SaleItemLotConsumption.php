<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemLotConsumption extends Model
{
    protected $fillable = [
        'sale_item_id',
        'lot_id',
        'quantity',
        'unit_cost_amount',
        'total_cost_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'lot_id');
    }
}
