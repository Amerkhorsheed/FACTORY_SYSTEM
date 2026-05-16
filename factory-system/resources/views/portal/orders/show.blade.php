@extends('layouts.app')
@section('title', __('portal.order_details'))
@section('page-title', __('portal.order_details'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="$order->order_number" :description="__('portal.order_details')" :back="route('portal.orders.index')" />

<div class="grid gap-6 lg:grid-cols-3">
    <x-card :title="__('portal.order_details')" class="lg:col-span-2">
        <div class="grid gap-4 sm:grid-cols-3">
            <div><p class="text-xs text-slate-500">{{ __('portal.order_date') }}</p><p class="font-bold">{{ $order->order_date?->format('Y-m-d') }}</p></div>
            <div><p class="text-xs text-slate-500">{{ __('portal.status') }}</p><x-status-badge :status="$order->status" /></div>
            <div><p class="text-xs text-slate-500">{{ __('portal.total') }}</p><p class="font-bold tabular-nums">{{ $money($order->total_amount) }}</p></div>
        </div>
    </x-card>

    <x-card :title="__('portal.payment_status')">
        @if($order->invoice)
            <p class="font-bold">{{ $order->invoice->invoice_number }}</p>
            <x-status-badge :status="$order->invoice->status" class="mt-3" />
            <p class="mt-3 text-sm font-bold tabular-nums">{{ $money($order->invoice->balance_due) }}</p>
        @else
            <x-empty-state />
        @endif
    </x-card>
</div>

<x-card :title="__('portal.product')" class="mt-6">
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>{{ __('portal.product') }}</th><th>{{ __('portal.quantity') }}</th><th>{{ __('portal.total') }}</th></tr></thead>
            <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }} {{ $item->product->unit }}</td>
                    <td class="font-bold tabular-nums">{{ $money($item->line_total) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@endsection
