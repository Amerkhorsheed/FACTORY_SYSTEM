@extends('layouts.app')
@section('title', __('expenses.expenses'))
@section('page-title', __('expenses.expenses'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('expenses.expenses')"><x-btn :href="route('erp.expenses.create')">{{ __('expenses.create') }}</x-btn></x-page-header>
<x-metric-card class="mb-5" :label="__('expenses.total_expenses')" :value="$money($total)" tone="red" />
<x-card>
    <x-filter-panel :action="route('erp.expenses.index')" :reset="route('erp.expenses.index')">
        <x-form-input name="category" :label="__('expenses.category')" :value="request('category')" />
        <x-form-input name="date_from" :label="__('ui.fields.from')" type="date" :value="request('date_from')" />
        <x-form-input name="date_to" :label="__('ui.fields.to')" type="date" :value="request('date_to')" />
    </x-filter-panel>

    <div class="table-scroll"><table class="table">
        <thead>
            <tr>
                <th scope="col">{{ __('expenses.category') }}</th>
                <th scope="col">{{ __('expenses.expense_date') }}</th>
                <th scope="col">{{ __('expenses.description') }}</th>
                <th scope="col">{{ __('expenses.amount') }}</th>
                <th scope="col" class="table-actions">{{ __('ui.labels.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($expenses as $expense)
            <tr>
                <td class="font-bold text-slate-900">{{ $expense->category }}</td>
                <td>{{ $expense->expense_date?->format('Y-m-d') }}</td>
                <td>{{ $expense->description }}</td>
                <td class="font-bold tabular-nums">{{ $money($expense->amount) }}</td>
                <td class="table-actions">
                    <a href="{{ route('erp.expenses.show', $expense) }}" class="action-link" aria-label="{{ __('ui.actions.show') }} {{ $expense->category }}">{{ __('ui.actions.show') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$expenses" />
</x-card>
@endsection
