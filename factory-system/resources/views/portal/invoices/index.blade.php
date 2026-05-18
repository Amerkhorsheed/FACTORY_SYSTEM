@extends('layouts.app')
@section('title', __('portal.my_invoices'))
@section('page-title', __('portal.my_invoices'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('portal.my_invoices')" :description="__('portal.invoices_description')" />

<div class="table-wrapper"><div class="table-scroll"><table class="table">
    <thead>
        <tr>
            <th scope="col">{{ __('portal.invoice_number') }}</th>
            <th scope="col">{{ __('invoices.issue_date') }}</th>
            <th scope="col">{{ __('invoices.due_date') }}</th>
            <th scope="col">{{ __('portal.status') }}</th>
            <th scope="col">{{ __('invoices.balance_due') }}</th>
            <th scope="col" class="table-actions">{{ __('ui.labels.actions') }}</th>
        </tr>
    </thead>
    <tbody>
    @forelse($invoices as $invoice)
        <tr>
            <td class="font-bold text-slate-900">{{ $invoice->invoice_number }}</td>
            <td>{{ $invoice->issue_date?->format('Y-m-d') }}</td>
            <td>{{ $invoice->due_date?->format('Y-m-d') }}</td>
            <td><x-status-badge :status="$invoice->status" /></td>
            <td class="font-bold tabular-nums">{{ $money($invoice->balance_due) }}</td>
            <td class="table-actions">
                <a class="action-link" href="{{ route('portal.invoices.show', $invoice) }}" aria-label="{{ __('ui.actions.show') }} {{ $invoice->invoice_number }}">{{ __('ui.actions.show') }}</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="6"><x-empty-state /></td></tr>
    @endforelse
    </tbody>
</table></div></div>
<x-pagination :paginator="$invoices" />
@endsection
