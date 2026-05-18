@extends('layouts.app')
@section('title', $order->order_number)
@section('page-title', __('ui.modules.orders'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="$order->order_number" :description="$order->customer?->name" :back="route('orders.index')">
    @can('update', $order)
        <x-btn :href="route('orders.edit', $order)" variant="secondary">{{ __('ui.actions.edit') }}</x-btn>
    @endcan
    @can('delete', $order)
        <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('{{ __('ui.actions.delete') }}?')">
            @csrf
            @method('DELETE')
            <x-btn type="submit" variant="danger">{{ __('ui.actions.delete') }}</x-btn>
        </form>
    @endcan
</x-page-header>

<div class="grid gap-6 lg:grid-cols-3">
    <x-card :title="__('portal.order_details')" class="lg:col-span-2">
        <dl class="grid gap-4 sm:grid-cols-3">
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.date') }}</dt><dd class="font-bold">{{ $order->order_date?->format('Y-m-d') }}</dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.status') }}</dt><dd><x-status-badge :status="$order->status" /></dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.total') }}</dt><dd class="font-bold tabular-nums">{{ $money($order->total_amount) }}</dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('portal.requested_delivery_date') }}</dt><dd>{{ $order->requested_delivery_date?->format('Y-m-d') ?? '-' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs text-slate-500">{{ __('ui.fields.notes') }}</dt><dd>{{ $order->notes ?: '-' }}</dd></div>
        </dl>
    </x-card>

    <x-card :title="__('ui.modules.invoices')">
        @if($order->invoice)
            <a class="action-link" href="{{ route('invoices.show', $order->invoice) }}" aria-label="{{ __('ui.actions.show') }} {{ $order->invoice->invoice_number }}">{{ $order->invoice->invoice_number }}</a>
            <p class="mt-2"><x-status-badge :status="$order->invoice->status" /></p>
            <p class="mt-2 text-sm font-bold tabular-nums">{{ $money($order->invoice->balance_due) }}</p>
        @else
            <x-empty-state />
        @endif
    </x-card>
</div>

<x-card :title="__('orders.workflow_actions')" class="mt-6">
    <div class="flex flex-wrap gap-3">
        @can('changeStatus', $order)
            @if($order->status === 'pending')
                <form method="POST" action="{{ route('orders.status.accept', $order) }}">@csrf<x-btn type="submit">{{ __('orders.accept') }}</x-btn></form>
            @endif
            @if($order->status === 'accepted')
                <form method="POST" action="{{ route('orders.status.preparing', $order) }}">@csrf<x-btn type="submit">{{ __('orders.mark_preparing') }}</x-btn></form>
            @endif
            @if($order->status === 'preparing')
                <form method="POST" action="{{ route('orders.status.ready', $order) }}">@csrf<x-btn type="submit">{{ __('orders.mark_ready') }}</x-btn></form>
            @endif
            @if($order->status === 'shipped')
                <form method="POST" action="{{ route('orders.status.returned', $order) }}">@csrf<x-btn type="submit" variant="secondary">{{ __('orders.record_return') }}</x-btn></form>
            @endif
        @endcan

        @can('confirmDelivery', $order)
            <form method="POST" action="{{ route('orders.status.deliver', $order) }}">@csrf<x-btn type="submit">{{ __('orders.confirm_delivery') }}</x-btn></form>
        @endcan
    </div>

    @can('cancel', $order)
        <form method="POST" action="{{ route('orders.status.cancel', $order) }}" class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]">
            @csrf
            <x-form-input name="reason" :label="__('orders.cancel_reason')" required />
            <div class="flex items-end"><x-btn type="submit" variant="danger">{{ __('orders.cancel_order') }}</x-btn></div>
        </form>
    @endcan
</x-card>

<x-card :title="__('portal.product')" class="mt-6">
    <div class="table-scroll"><table class="table">
        <thead><tr><th>{{ __('portal.product') }}</th><th>{{ __('ui.fields.quantity') }}</th><th>{{ __('ui.fields.unit_price') }}</th><th>{{ __('ui.fields.total') }}</th></tr></thead>
        <tbody>
        @foreach($order->items as $item)
            <tr><td>{{ $item->product?->name }}</td><td>{{ $item->quantity }}</td><td>{{ $money($item->unit_price) }}</td><td class="font-bold tabular-nums">{{ $money($item->line_total) }}</td></tr>
        @endforeach
        </tbody>
    </table></div>
</x-card>
@endsection
