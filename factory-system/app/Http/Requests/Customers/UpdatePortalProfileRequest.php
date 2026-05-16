<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePortalProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('customer') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30'],
            'phone_alt' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
        ];
    }
}
