<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('system.settings.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'factory_name' => ['required', 'string', 'max:150'],
            'factory_address' => ['nullable', 'string', 'max:255'],
            'factory_phone' => ['nullable', 'string', 'max:30'],
            'factory_tax_number' => ['nullable', 'string', 'max:80'],
            'factory_logo' => ['nullable', 'image', 'max:2048'],
            'invoice_prefix' => ['required', 'string', 'max:20'],
            'invoice_due_days' => ['required', 'integer', 'min:0', 'max:365'],
            'invoice_tax_rate' => ['required', 'integer', 'min:0', 'max:100'],
            'invoice_footer_text' => ['nullable', 'string', 'max:500'],
            'invoice_bank_details' => ['nullable', 'string', 'max:1000'],
            'invoice_terms' => ['nullable', 'string', 'max:1000'],
            'default_low_threshold' => ['required', 'integer', 'min:0'],
            'enable_stock_warnings' => ['nullable', 'boolean'],
            'default_credit_limit' => ['required', 'integer', 'min:0'],
            'default_category' => ['required', Rule::in(['A', 'B', 'C'])],
            'enable_arabic_numerals' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'factory_name' => __('admin.factory_name'),
            'invoice_due_days' => __('admin.invoice_due_days'),
            'default_low_threshold' => __('admin.default_low_threshold'),
            'default_credit_limit' => __('admin.default_credit_limit'),
        ];
    }
}
