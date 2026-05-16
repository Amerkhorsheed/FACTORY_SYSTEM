@extends('layouts.app')
@section('title', __('portal.my_orders'))
@section('page-title', __('portal.my_orders'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('portal.my_orders')" :description="__('portal.orders_description')">
    <x-btn :href="route('portal.orders.create')">{{ __('portal.create_order') }}</x-btn>
</x-page-header>

<div class="table-wrapper">
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>{{ __('portal.order_number') }}</th><th>{{ __('portal.order_date') }}</th><th>{{ __('portal.status') }}</th><th>{{ __('portal.total') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($orders as $order)
                <tr>
                    <td class="font-bold">{{ $order->order_number }}</td>
                    <td>{{ $order->order_date?->format('Y-m-d') }}</td>
                    <td><x-status-badge :status="$order->status" /></td>
                    <td class="font-bold tabular-nums">{{ $money($order->total_amount) }}</td>
                    <td><a class="text-sm font-bold text-brand-700" href="{{ route('portal.orders.show', $order) }}">{{ __('ui.actions.show') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-empty-state /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<x-pagination :paginator="$orders" />
@endsection
