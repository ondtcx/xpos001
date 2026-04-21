<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id',
        'payment_method',
        'amount',
        'is_reversed',
        'received_at',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'notes',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'reversed_at' => 'datetime',
        'is_reversed' => 'boolean',
    ];

    public function isReversed(): bool
    {
        return $this->is_reversed;
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
