<?php

namespace App\Http\Requests\Purchases;

use App\Models\PurchaseItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class DetailedPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'invoice_number' => $this->invoiceNumberRules(),
            'purchased_at' => ['required', 'date'],
            'payment_type' => ['required', Rule::in(['cash', 'transfer', 'credit'])],
            'global_discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'global_tax_iva_amount' => ['nullable', 'numeric', 'gte:0'],
            'global_tax_ice_amount' => ['nullable', 'numeric', 'gte:0'],
            'global_tax_other_amount' => ['nullable', 'numeric', 'gte:0'],
            'extra_costs_amount' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.line_type' => ['required', Rule::in([PurchaseItem::LINE_TYPE_NORMAL, PurchaseItem::LINE_TYPE_BONUS])],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.bonus_quantity' => ['nullable', 'numeric', 'gte:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'items.*.manual_total_cost' => ['nullable', 'numeric', 'gte:0'],
            'items.*.line_discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_iva_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_ice_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_other_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.eligible_for_global_iva' => ['nullable', 'boolean'],
            'items.*.eligible_for_global_ice' => ['nullable', 'boolean'],
            'items.*.eligible_for_global_other' => ['nullable', 'boolean'],
            'items.*.expiration_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('items', []) as $index => $item) {
                $lineType = $item['line_type'] ?? PurchaseItem::LINE_TYPE_NORMAL;

                if ($lineType === PurchaseItem::LINE_TYPE_NORMAL && ! array_key_exists('unit_cost', $item)) {
                    $validator->errors()->add("items.$index.unit_cost", 'El costo unitario es obligatorio en líneas normales.');
                }

                if ($lineType === PurchaseItem::LINE_TYPE_BONUS && ! array_key_exists('manual_total_cost', $item)) {
                    $validator->errors()->add("items.$index.manual_total_cost", 'El costo manual total es obligatorio en líneas de bonificación.');
                }
            }
        });
    }

    protected function invoiceNumberRules(): array
    {
        return [
            'nullable',
            'string',
            'max:255',
            $this->invoiceUniqueRule(),
        ];
    }

    abstract protected function invoiceUniqueRule(): \Illuminate\Validation\Rules\Unique;
}
