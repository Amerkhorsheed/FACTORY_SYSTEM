<?php

namespace App\Http\Requests\Erp;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('erp.expenses.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'amount' => ['sometimes', 'required', 'integer', 'min:1'],
            'expense_date' => ['sometimes', 'required', 'date'],
            'description' => ['sometimes', 'required', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:100'],
            'attachment' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category' => __('expenses.category'),
            'amount' => __('expenses.amount'),
            'expense_date' => __('expenses.expense_date'),
            'description' => __('expenses.description'),
            'reference' => __('expenses.reference'),
            'attachment' => __('expenses.attachment'),
        ];
    }
}
