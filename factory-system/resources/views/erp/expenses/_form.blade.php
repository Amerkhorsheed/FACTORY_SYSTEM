@csrf
<x-card>
    <div class="grid gap-4 md:grid-cols-3"><x-form-input name="category" :label="__('expenses.category')" :value="$expense->category ?? null" required /><x-form-input name="amount" :label="__('expenses.amount')" type="number" :value="$expense->amount ?? null" required /><x-form-input name="expense_date" :label="__('expenses.expense_date')" type="date" :value="isset($expense) ? $expense->expense_date?->format('Y-m-d') : today()->toDateString()" required /><x-form-input name="reference" :label="__('expenses.reference')" :value="$expense->reference ?? null" /><x-form-input name="attachment" :label="__('expenses.attachment')" :value="$expense->attachment ?? null" /></div>
    <div class="mt-4"><x-form-textarea name="description" :label="__('expenses.description')" required>{{ $expense->description ?? '' }}</x-form-textarea></div>
    <x-btn type="submit" class="mt-5">{{ __('ui.actions.save') }}</x-btn>
</x-card>
