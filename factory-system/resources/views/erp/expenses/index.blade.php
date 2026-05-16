@extends('layouts.app')
@section('title', __('expenses.expenses'))
@section('page-title', __('expenses.expenses'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('expenses.expenses')"><x-btn :href="route('erp.expenses.create')">{{ __('expenses.create') }}</x-btn></x-page-header>
<x-metric-card class="mb-5" :label="__('expenses.total_expenses')" :value="$money($total)" tone="red" />
<x-card>
    <form method="GET" action="{{ route('erp.expenses.index') }}" class="mb-5 grid gap-3 md:grid-cols-5"><x-form-input name="category" :label="__('expenses.category')" :value="request('category')" /><x-form-input name="date_from" :label="__('ui.fields.from')" type="date" :value="request('date_from')" /><x-form-input name="date_to" :label="__('ui.fields.to')" type="date" :value="request('date_to')" /><div class="flex items-end"><x-btn type="submit">{{ __('ui.actions.search') }}</x-btn></div></form>
    <div class="table-scroll"><table class="table"><thead><tr><th>{{ __('expenses.category') }}</th><th>{{ __('expenses.expense_date') }}</th><th>{{ __('expenses.description') }}</th><th>{{ __('expenses.amount') }}</th><th></th></tr></thead><tbody>@forelse($expenses as $expense)<tr><td class="font-bold">{{ $expense->category }}</td><td>{{ $expense->expense_date?->format('Y-m-d') }}</td><td>{{ $expense->description }}</td><td class="font-bold tabular-nums">{{ $money($expense->amount) }}</td><td><a href="{{ route('erp.expenses.show', $expense) }}" class="font-bold text-brand-700">{{ __('ui.actions.show') }}</a></td></tr>@empty<tr><td colspan="5"><x-empty-state /></td></tr>@endforelse</tbody></table></div><x-pagination :paginator="$expenses" />
</x-card>
@endsection
