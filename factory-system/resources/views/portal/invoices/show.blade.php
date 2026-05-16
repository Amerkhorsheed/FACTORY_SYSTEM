@extends('layouts.app')
@section('title', __('portal.invoice_details'))
@section('page-title', __('portal.invoice_details'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="$invoice->invoice_number" :description="__('portal.invoice_details')" :back="route('portal.invoices.index')" />

<div class="grid gap-6 lg:grid-cols-3">
    <x-card :title="__('portal.invoice_details')" class="lg:col-span-2">
        <div class="grid gap-4 sm:grid-cols-4">
            <div><p class="text-xs text-slate-500">{{ __('invoices.issue_date') }}</p><p class="font-bold">{{ $invoice->issue_date?->format('Y-m-d') }}</p></div>
            <div><p class="text-xs text-slate-500">{{ __('invoices.due_date') }}</p><p class="font-bold">{{ $invoice->due_date?->format('Y-m-d') }}</p></div>
            <div><p class="text-xs text-slate-500">{{ __('portal.status') }}</p><x-status-badge :status="$invoice->status" /></div>
            <div><p class="text-xs text-slate-500">{{ __('invoices.balance_due') }}</p><p class="font-bold tabular-nums">{{ $money($invoice->balance_due) }}</p></div>
        </div>
    </x-card>
    <x-card :title="__('invoices.total')">
        <p class="text-2xl font-black tabular-nums text-brand-700">{{ $money($invoice->total_amount) }}</p>
        <p class="mt-2 text-sm text-slate-500">{{ __('invoices.paid') }}: {{ $money($invoice->paid_amount) }}</p>
    </x-card>
</div>

<x-card :title="__('invoices.payments')" class="mt-6">
    @forelse($invoice->payments as $payment)
        <div class="flex items-center justify-between border-b border-slate-100 py-3 last:border-0">
            <p class="font-bold tabular-nums">{{ $money($payment->amount) }}</p>
            <p class="text-sm text-slate-500">{{ $payment->payment_date?->format('Y-m-d') }}</p>
        </div>
    @empty
        <x-empty-state />
    @endforelse
</x-card>
@endsection
