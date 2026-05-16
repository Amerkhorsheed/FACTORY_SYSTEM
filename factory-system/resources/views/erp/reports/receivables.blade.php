@extends('layouts.app')
@section('title', __('erp.receivables_report'))
@section('page-title', __('erp.receivables_report'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('erp.receivables_report')" />
<x-metric-card class="mb-5" :label="__('erp.overdue_amount')" :value="$money($totalOverdue)" tone="red" />
<x-card><div class="table-scroll"><table class="table"><thead><tr><th>{{ __('invoices.number') }}</th><th>{{ __('ui.fields.customer') }}</th><th>{{ __('invoices.due_date') }}</th><th>{{ __('invoices.balance_due') }}</th></tr></thead><tbody>@forelse($invoices as $invoice)<tr><td>{{ $invoice->invoice_number }}</td><td>{{ $invoice->customer?->name }}</td><td>{{ $invoice->due_date?->format('Y-m-d') }}</td><td class="font-bold tabular-nums">{{ $money($invoice->balance_due) }}</td></tr>@empty<tr><td colspan="4"><x-empty-state /></td></tr>@endforelse</tbody></table></div><x-pagination :paginator="$invoices" /></x-card>
@endsection
