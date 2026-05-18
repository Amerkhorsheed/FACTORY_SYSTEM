@extends('layouts.app')
@section('title', __('invoices.invoices'))
@section('page-title', __('invoices.invoices'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('invoices.invoices')" />
<x-card>
    <x-filter-panel :action="route('invoices.index')" :reset="route('invoices.index')">
        <x-form-input name="search" :label="__('ui.actions.search')" :value="request('search')" placeholder="{{ __('invoices.number') }} / {{ __('ui.fields.customer') }}" />
        <x-form-select name="status" :label="__('ui.fields.status')">
            <option value="">{{ __('ui.labels.all_statuses') }}</option>
            @foreach(['draft','issued','partial','paid','void'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('ui.status.'.$s) }}</option>
            @endforeach
        </x-form-select>
        <x-form-input name="date_from" :label="__('ui.fields.from')" type="date" :value="request('date_from')" />
        <x-form-input name="date_to" :label="__('ui.fields.to')" type="date" :value="request('date_to')" />
    </x-filter-panel>

    <div class="table-scroll"><table class="table">
        <thead>
            <tr>
                <th scope="col">{{ __('invoices.number') }}</th>
                <th scope="col">{{ __('ui.fields.customer') }}</th>
                <th scope="col">{{ __('invoices.issue_date') }}</th>
                <th scope="col">{{ __('ui.fields.status') }}</th>
                <th scope="col">{{ __('invoices.balance_due') }}</th>
                <th scope="col" class="table-actions">{{ __('ui.labels.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($invoices as $invoice)
            <tr>
                <td class="font-bold text-slate-900">{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->customer?->name }}</td>
                <td>{{ $invoice->issue_date?->format('Y-m-d') }}</td>
                <td><x-status-badge :status="$invoice->status" /></td>
                <td class="font-bold tabular-nums">{{ $money($invoice->balance_due) }}</td>
                <td class="table-actions">
                    <a href="{{ route('invoices.show', $invoice) }}" class="action-link" aria-label="{{ __('ui.actions.show') }} {{ $invoice->invoice_number }}">{{ __('ui.actions.show') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$invoices" />
</x-card>
@endsection
