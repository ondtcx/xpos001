<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Purchase extends Model
{
    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_VOIDED = 'voided';

    public const ENTRY_MODE_QUICK = 'quick';

    public const ENTRY_MODE_DETAILED = 'detailed';

    protected $fillable = [
        'supplier_id',
        'invoice_number',
        'purchased_at',
        'payment_type',
        'entry_mode',
        'is_credit',
        'subtotal_amount',
        'global_discount_amount',
        'global_tax_amount',
        'global_tax_iva_amount',
        'global_tax_ice_amount',
        'global_tax_other_amount',
        'extra_costs_amount',
        'total_amount',
        'status',
        'voided_at',
        'voided_by',
        'void_reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'is_credit' => 'boolean',
        'voided_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function lots(): HasManyThrough
    {
        return $this->hasManyThrough(InventoryLot::class, PurchaseItem::class, 'purchase_id', 'purchase_item_id');
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function isDetailed(): bool
    {
        return $this->entry_mode === self::ENTRY_MODE_DETAILED;
    }
}
