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

        $paymentMethod = $this->input('payment_method', 'cash');
        $allowCreditSale = $this->boolean('allow_credit_sale');
        $confirmCreditSale = $this->boolean('confirm_credit_sale');

        // Translate new AJAX payload field `metodo` to legacy fields the
        // builder expects. Old form-submit view does not send `metodo`, so
        // this is a no-op for the legacy path.
        $metodo = $this->input('metodo');
        if (is_string($metodo) && $metodo !== '') {
            $paymentMethod = match ($metodo) {
                'efectivo' => 'cash',
                'transfer' => 'transfer',
                'fiado' => 'cash',
                default => $paymentMethod,
            };
            if ($metodo === 'fiado') {
                $allowCreditSale = true;
                $confirmCreditSale = true;
            }
        }

        $this->merge([
            'items' => $items,
            'action' => $this->input('action', 'checkout'),
            'metodo' => $metodo,
            'payment_method' => $paymentMethod,
            'received_amount' => $this->input('received_amount'),
            'allow_credit_sale' => $allowCreditSale,
            'confirm_credit_sale' => $confirmCreditSale,
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
