@extends('layouts.app')
@section('title', __('ui.modules.dashboard'))
@section('page-title', __('ui.modules.dashboard'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="$customer->business_name ?: $customer->name" :description="$customer->name.' | '.__('portal.dashboard_description')">
    <x-btn :href="route('portal.orders.create')">{{ __('portal.create_order') }}</x-btn>
</x-page-header>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <x-metric-card :label="__('portal.total_orders')" :value="$customer->orders_count" />
    <x-metric-card :label="__('portal.total_invoices')" :value="$customer->invoices_count" tone="green" />
    <x-metric-card :label="__('portal.outstanding_balance')" :value="$money($customer->outstanding_balance)" tone="red" />
    <x-metric-card :label="__('portal.available_credit')" :value="$money($customer->available_credit)" tone="amber" />
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <x-card :title="__('portal.recent_orders')">
        @forelse($recentOrders as $order)
            <a href="{{ route('portal.orders.show', $order) }}" class="flex items-center justify-between border-b border-slate-100 py-3 last:border-0">
                <div>
                    <p class="font-bold text-slate-800">{{ $order->order_number }}</p>
                    <p class="text-xs text-slate-500">{{ $order->order_date?->format('Y-m-d') }}</p>
                </div>
                <div class="text-left">
                    <x-status-badge :status="$order->status" />
                    <p class="mt-1 text-sm font-bold tabular-nums">{{ $money($order->total_amount) }}</p>
                </div>
            </a>
        @empty
            <x-empty-state :title="__('ui.messages.empty_title')" />
        @endforelse
    </x-card>

    <x-card :title="__('portal.unpaid_invoices')">
        @forelse($unpaidInvoices as $invoice)
            <a href="{{ route('portal.invoices.show', $invoice) }}" class="flex items-center justify-between border-b border-slate-100 py-3 last:border-0">
                <div>
                    <p class="font-bold text-slate-800">{{ $invoice->invoice_number }}</p>
                    <p class="text-xs text-slate-500">{{ $invoice->due_date?->format('Y-m-d') }}</p>
                </div>
                <div class="text-left">
                    <x-status-badge :status="$invoice->status" />
                    <p class="mt-1 text-sm font-bold tabular-nums">{{ $money($invoice->balance_due) }}</p>
                </div>
            </a>
        @empty
            <x-empty-state :title="__('ui.messages.empty_title')" />
        @endforelse
    </x-card>
</div>
@endsection
