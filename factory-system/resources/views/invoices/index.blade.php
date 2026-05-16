@extends('layouts.app')
@section('title', __('invoices.invoices'))
@section('page-title', __('invoices.invoices'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('invoices.invoices')" />
<x-card>
    <form method="GET" action="{{ route('invoices.index') }}" class="mb-5 grid gap-3 md:grid-cols-5"><x-form-input name="search" :label="__('ui.actions.search')" :value="request('search')" /><x-form-select name="status" :label="__('ui.fields.status')"><option value="">{{ __('ui.actions.search') }}</option>@foreach(['draft','issued','partial','paid','void'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __('ui.status.'.$s) }}</option>@endforeach</x-form-select><x-form-input name="date_from" :label="__('ui.fields.from')" type="date" :value="request('date_from')" /><x-form-input name="date_to" :label="__('ui.fields.to')" type="date" :value="request('date_to')" /><div class="flex items-end"><x-btn type="submit">{{ __('ui.actions.search') }}</x-btn></div></form>
    <div class="table-scroll"><table class="table"><thead><tr><th>{{ __('invoices.number') }}</th><th>{{ __('ui.fields.customer') }}</th><th>{{ __('invoices.issue_date') }}</th><th>{{ __('ui.fields.status') }}</th><th>{{ __('invoices.balance_due') }}</th><th></th></tr></thead><tbody>@forelse($invoices as $invoice)<tr><td class="font-bold">{{ $invoice->invoice_number }}</td><td>{{ $invoice->customer?->name }}</td><td>{{ $invoice->issue_date?->format('Y-m-d') }}</td><td><x-status-badge :status="$invoice->status" /></td><td class="font-bold tabular-nums">{{ $money($invoice->balance_due) }}</td><td><a href="{{ route('invoices.show', $invoice) }}" class="font-bold text-brand-700">{{ __('ui.actions.show') }}</a></td></tr>@empty<tr><td colspan="6"><x-empty-state /></td></tr>@endforelse</tbody></table></div><x-pagination :paginator="$invoices" />
</x-card>
@endsection
