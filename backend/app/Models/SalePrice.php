<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SalePrice extends Model
{
    protected $fillable = [
        'sale_presentation_id',
        'price_amount',
        'min_price_amount',
        'suggested_margin_percent',
        'starts_at',
        'ends_at',
        'created_by',
        'reason',
    ];

    protected $casts = [
        'suggested_margin_percent' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(SalePresentation::class, 'sale_presentation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
