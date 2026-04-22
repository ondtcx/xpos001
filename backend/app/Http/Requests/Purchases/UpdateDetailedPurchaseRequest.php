<?php

namespace App\Http\Requests\Purchases;

use App\Models\Purchase;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class UpdateDetailedPurchaseRequest extends DetailedPurchaseRequest
{
    protected function invoiceUniqueRule(): Unique
    {
        /** @var Purchase|null $purchase */
        $purchase = $this->route('purchase');

        return Rule::unique('purchases')
            ->ignore($purchase?->id)
            ->where(function ($query) {
                return $query->where('supplier_id', $this->input('supplier_id'));
            });
    }
}
