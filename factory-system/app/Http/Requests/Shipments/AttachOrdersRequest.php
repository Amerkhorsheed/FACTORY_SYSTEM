<?php

namespace App\Http\Requests\Shipments;

use Illuminate\Foundation\Http\FormRequest;

class AttachOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('shipments.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'order_ids' => ['required', 'array'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'order_ids' => __('shipments.orders'),
        ];
    }
}
