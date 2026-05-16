<?php

namespace App\Http\Requests\Erp;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('erp.expenses.create') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:1'],
            'expense_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
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
