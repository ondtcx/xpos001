<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningInventoryEntry extends Model
{
    protected $fillable = [
        'variant_id',
        'quantity',
        'estimated_unit_cost_amount',
        'recorded_at',
        'is_audited',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'recorded_at' => 'datetime',
        'is_audited' => 'boolean',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
