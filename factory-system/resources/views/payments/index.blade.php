@extends('layouts.app')
@section('title', __('payments.payments'))
@section('page-title', __('payments.payments'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('payments.payments')" />
<x-metric-card class="mb-5" :label="__('payments.total_payments')" :value="$money($total)" tone="green" />
<x-card><div class="table-scroll"><table class="table"><thead><tr><th>#</th><th>{{ __('payments.customer') }}</th><th>{{ __('payments.invoice') }}</th><th>{{ __('payments.date') }}</th><th>{{ __('payments.amount') }}</th><th></th></tr></thead><tbody>@forelse($payments as $payment)<tr><td class="font-bold">{{ $payment->payment_number }}</td><td>{{ $payment->customer?->name }}</td><td>{{ $payment->invoice?->invoice_number }}</td><td>{{ $payment->payment_date?->format('Y-m-d') }}</td><td class="font-bold tabular-nums">{{ $money($payment->amount) }}</td><td><a href="{{ route('payments.show', $payment) }}" class="font-bold text-brand-700">{{ __('ui.actions.show') }}</a></td></tr>@empty<tr><td colspan="6"><x-empty-state /></td></tr>@endforelse</tbody></table></div><x-pagination :paginator="$payments" /></x-card>
@endsection
