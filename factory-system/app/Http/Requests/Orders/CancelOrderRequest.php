<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates order cancellation payload.
 */
class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('orders.cancel');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
