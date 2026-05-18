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
            <thead>
                <tr>
                    <th scope="col">{{ __('portal.order_number') }}</th>
                    <th scope="col">{{ __('portal.order_date') }}</th>
                    <th scope="col">{{ __('portal.status') }}</th>
                    <th scope="col">{{ __('portal.total') }}</th>
                    <th scope="col" class="table-actions">{{ __('ui.labels.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($orders as $order)
                <tr>
                    <td class="font-bold text-slate-900">{{ $order->order_number }}</td>
                    <td>{{ $order->order_date?->format('Y-m-d') }}</td>
                    <td><x-status-badge :status="$order->status" /></td>
                    <td class="font-bold tabular-nums">{{ $money($order->total_amount) }}</td>
                    <td class="table-actions">
                        <a class="action-link" href="{{ route('portal.orders.show', $order) }}" aria-label="{{ __('ui.actions.show') }} {{ $order->order_number }}">{{ __('ui.actions.show') }}</a>
                    </td>
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
