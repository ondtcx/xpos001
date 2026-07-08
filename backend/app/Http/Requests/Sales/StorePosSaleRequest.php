<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => filled($item['sale_presentation_id'] ?? null) && filled($item['quantity'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'items' => $items,
            'action' => $this->input('action', 'checkout'),
            'payment_method' => $this->input('payment_method', 'cash'),
            'received_amount' => $this->input('received_amount'),
            'allow_credit_sale' => $this->boolean('allow_credit_sale'),
            'confirm_credit_sale' => $this->boolean('confirm_credit_sale'),
            'mixed_payments' => [
                'cash' => $this->input('mixed_payments.cash', 0),
                'transfer' => $this->input('mixed_payments.transfer', 0),
            ],
        ]);
    }

    public function rules(): array
    {
        $isCheckout = $this->input('action', 'checkout') === 'checkout';

        return [
            'action' => ['required', Rule::in(['checkout', 'complete'])],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'mixed'])],
            'received_amount' => ['nullable', 'numeric', 'min:0'],
            'allow_credit_sale' => ['boolean'],
            'confirm_credit_sale' => ['boolean'],
            'mixed_payments' => ['array'],
            'mixed_payments.cash' => ['nullable', 'numeric', 'min:0'],
            'mixed_payments.transfer' => ['nullable', 'numeric', 'min:0'],
            'items' => [Rule::requiredIf($isCheckout), 'array'],
            'items.*.sale_presentation_id' => ['required', 'exists:sale_presentations,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Agrega al menos un producto antes de cobrar desde POS.',
        ];
    }
}
