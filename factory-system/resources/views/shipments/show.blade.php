@extends('layouts.app')
@section('title', $shipment->shipment_number)
@section('page-title', __('shipments.shipments'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="$shipment->shipment_number" :description="$shipment->shipment_date?->format('Y-m-d')" :back="route('shipments.index')">
    @can('update', $shipment)
        <x-btn :href="route('shipments.edit', $shipment)" variant="secondary">{{ __('ui.actions.edit') }}</x-btn>
    @endcan
    @can('viewManifest', $shipment)
        <x-btn :href="route('shipments.manifest', $shipment)" variant="secondary">{{ __('shipments.manifest') }}</x-btn>
    @endcan
</x-page-header>

<div class="grid gap-6 lg:grid-cols-3">
    <x-card :title="__('shipments.shipment')" class="lg:col-span-2">
        <dl class="grid gap-4 sm:grid-cols-3">
            <div><dt class="text-xs text-slate-500">{{ __('shipments.truck') }}</dt><dd class="font-bold">{{ $shipment->truck?->plate_number }}</dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('shipments.driver') }}</dt><dd class="font-bold">{{ $shipment->driver?->name }}</dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.status') }}</dt><dd><x-status-badge :status="$shipment->status" /></dd></div>
        </dl>
    </x-card>
    <x-card :title="__('shipments.orders')"><p class="text-3xl font-black text-brand-700">{{ $shipment->orders->count() }}</p></x-card>
</div>

<x-card :title="__('orders.workflow_actions')" class="mt-6">
    <div class="flex flex-wrap gap-3">
        @can('dispatch', $shipment)
            @if($shipment->orders->where('status', 'ready')->isNotEmpty())
                <form method="POST" action="{{ route('shipments.dispatch', $shipment) }}">@csrf<x-btn type="submit">{{ __('shipments.dispatch') }}</x-btn></form>
            @endif
        @endcan
    </div>

    @can('cancel', $shipment)
        <form method="POST" action="{{ route('shipments.status.cancel', $shipment) }}" class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]">
            @csrf
            <x-form-input name="reason" :label="__('orders.cancel_reason')" required />
            <div class="flex items-end"><x-btn type="submit" variant="danger">{{ __('shipments.cancel') }}</x-btn></div>
        </form>
    @endcan
</x-card>

<x-card :title="__('shipments.orders')" class="mt-6">
    <div class="table-scroll"><table class="table">
        <thead><tr><th>{{ __('portal.order_number') }}</th><th>{{ __('ui.fields.customer') }}</th><th>{{ __('ui.fields.status') }}</th><th>{{ __('ui.fields.total') }}</th><th></th></tr></thead>
        <tbody>
        @forelse($shipment->orders as $order)
            <tr>
                <td><a class="font-bold text-brand-700" href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></td>
                <td>{{ $order->customer?->name }}</td>
                <td><x-status-badge :status="$order->status" /></td>
                <td>{{ $money($order->total_amount) }}</td>
                <td class="flex flex-wrap gap-2">
                    @can('update', $shipment)
                        @if(in_array($shipment->status, ['planned', 'loading'], true) && $order->status === 'ready')
                            <form method="POST" action="{{ route('shipments.detach-order', [$shipment, $order]) }}">@csrf<x-btn type="submit" size="sm" variant="ghost">{{ __('shipments.detach') }}</x-btn></form>
                        @endif
                    @endcan
                    @can('updateStatus', $shipment)
                        @if($shipment->status === 'dispatched' && $order->status === 'shipped')
                            <form method="POST" action="{{ route('shipments.orders.delivered', [$shipment, $order]) }}">@csrf<x-btn type="submit" size="sm">{{ __('shipments.deliver') }}</x-btn></form>
                        @endif
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
</x-card>

@can('update', $shipment)
    @if($readyOrders->isNotEmpty() && in_array($shipment->status, ['planned', 'loading'], true))
        <x-card :title="__('shipments.attach_orders')" class="mt-6">
            <form method="POST" action="{{ route('shipments.attach-orders', $shipment) }}" class="space-y-3">
                @csrf
                @foreach($readyOrders as $order)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="rounded border-slate-300 text-brand-600"> {{ $order->order_number }} - {{ $order->customer?->name }}</label>
                @endforeach
                <x-btn type="submit">{{ __('ui.actions.save') }}</x-btn>
            </form>
        </x-card>
    @endif
@endcan
@endsection
