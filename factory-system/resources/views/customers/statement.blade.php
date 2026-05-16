@extends('layouts.app')
@section('title', __('customers.statement'))
@section('page-title', __('customers.statement'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('customers.statement')" :description="$customer->name" :back="route('customers.show', $customer)" />
<div class="mb-5 grid gap-4 md:grid-cols-2"><x-metric-card :label="__('portal.outstanding_balance')" :value="$money($statement['closing_balance'] ?? 0)" tone="red" /></div>
<x-card :title="__('ui.modules.invoices')">
    @forelse($statement['invoices'] as $invoice)
        <div class="flex justify-between border-b border-slate-100 py-3 last:border-0"><span class="font-bold">{{ $invoice->invoice_number }}</span><span class="tabular-nums">{{ $money($invoice->balance_due) }}</span></div>
    @empty
        <x-empty-state />
    @endforelse
</x-card>
@endsection
