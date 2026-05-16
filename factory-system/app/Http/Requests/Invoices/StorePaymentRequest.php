<?php

namespace App\Http\Requests\Invoices;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('payments.create') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,credit,check,bank_transfer'],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => __('invoices.payment_amount'),
            'payment_method' => __('invoices.payment_method'),
            'payment_date' => __('invoices.payment_date'),
            'reference_number' => __('invoices.reference_number'),
            'notes' => __('invoices.notes'),
        ];
    }
}
