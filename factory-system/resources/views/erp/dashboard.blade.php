@extends('layouts.app')
@section('title', __('erp.dashboard'))
@section('page-title', __('erp.dashboard'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('erp.dashboard')" :description="__('ui.modules.reports')">
    <x-btn :href="route('erp.reports.sales')" variant="secondary">{{ __('ui.modules.reports') }}</x-btn>
</x-page-header>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <x-metric-card :label="__('erp.today_sales')" :value="$money($kpis['today_sales'] ?? 0)" />
    <x-metric-card :label="__('erp.month_sales')" :value="$money($kpis['month_sales'] ?? 0)" tone="green" />
    <x-metric-card :label="__('erp.overdue_amount')" :value="$money($kpis['overdue_amount'] ?? 0)" tone="red" />
    <x-metric-card :label="__('erp.today_expenses')" :value="$money($kpis['today_expenses'] ?? 0)" tone="amber" />
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <x-card :title="__('portal.recent_orders')">
        @forelse($recentOrders as $order)
            <a href="{{ route('orders.show', $order) }}" class="flex items-center justify-between border-b border-slate-100 py-3 last:border-0">
                <div><p class="font-bold">{{ $order->order_number }}</p><p class="text-xs text-slate-500">{{ $order->customer?->name }}</p></div>
                <x-status-badge :status="$order->status" />
            </a>
        @empty
            <x-empty-state />
        @endforelse
    </x-card>

    <x-card :title="__('ui.modules.invoices')">
        @forelse($recentInvoices as $invoice)
            <a href="{{ route('invoices.show', $invoice) }}" class="flex items-center justify-between border-b border-slate-100 py-3 last:border-0">
                <div><p class="font-bold">{{ $invoice->invoice_number }}</p><p class="text-xs text-slate-500">{{ $invoice->customer?->name }}</p></div>
                <p class="font-bold tabular-nums">{{ $money($invoice->balance_due) }}</p>
            </a>
        @empty
            <x-empty-state />
        @endforelse
    </x-card>
</div>
@endsection
