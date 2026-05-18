@extends('layouts.app')
@section('title', __('payments.payments'))
@section('page-title', __('payments.payments'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('payments.payments')" />
<x-metric-card class="mb-5" :label="__('payments.total_payments')" :value="$money($total)" tone="green" />
<x-card>
    <div class="table-scroll"><table class="table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">{{ __('payments.customer') }}</th>
                <th scope="col">{{ __('payments.invoice') }}</th>
                <th scope="col">{{ __('payments.date') }}</th>
                <th scope="col">{{ __('payments.amount') }}</th>
                <th scope="col" class="table-actions">{{ __('ui.labels.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($payments as $payment)
            <tr>
                <td class="font-bold text-slate-900">{{ $payment->payment_number }}</td>
                <td>{{ $payment->customer?->name }}</td>
                <td>{{ $payment->invoice?->invoice_number }}</td>
                <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                <td class="font-bold tabular-nums">{{ $money($payment->amount) }}</td>
                <td class="table-actions">
                    <a href="{{ route('payments.show', $payment) }}" class="action-link" aria-label="{{ __('ui.actions.show') }} {{ $payment->payment_number }}">{{ __('ui.actions.show') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$payments" />
</x-card>
@endsection
