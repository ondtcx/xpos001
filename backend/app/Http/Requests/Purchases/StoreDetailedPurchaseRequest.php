<?php

namespace App\Http\Requests\Purchases;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class StoreDetailedPurchaseRequest extends DetailedPurchaseRequest
{
    protected function invoiceUniqueRule(): Unique
    {
        return Rule::unique('purchases')->where(function ($query) {
            return $query->where('supplier_id', $this->input('supplier_id'));
        });
    }
}
