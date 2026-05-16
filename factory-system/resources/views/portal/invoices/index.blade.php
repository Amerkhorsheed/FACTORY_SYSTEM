@extends('layouts.app')
@section('title', __('portal.my_invoices'))
@section('page-title', __('portal.my_invoices'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('portal.my_invoices')" :description="__('portal.invoices_description')" />

<div class="table-wrapper"><div class="table-scroll"><table class="table">
    <thead><tr><th>{{ __('portal.invoice_number') }}</th><th>{{ __('invoices.issue_date') }}</th><th>{{ __('invoices.due_date') }}</th><th>{{ __('portal.status') }}</th><th>{{ __('invoices.balance_due') }}</th><th></th></tr></thead>
    <tbody>
    @forelse($invoices as $invoice)
        <tr>
            <td class="font-bold">{{ $invoice->invoice_number }}</td>
            <td>{{ $invoice->issue_date?->format('Y-m-d') }}</td>
            <td>{{ $invoice->due_date?->format('Y-m-d') }}</td>
            <td><x-status-badge :status="$invoice->status" /></td>
            <td class="font-bold tabular-nums">{{ $money($invoice->balance_due) }}</td>
            <td><a class="text-sm font-bold text-brand-700" href="{{ route('portal.invoices.show', $invoice) }}">{{ __('ui.actions.show') }}</a></td>
        </tr>
    @empty
        <tr><td colspan="6"><x-empty-state /></td></tr>
    @endforelse
    </tbody>
</table></div></div>
<x-pagination :paginator="$invoices" />
@endsection
