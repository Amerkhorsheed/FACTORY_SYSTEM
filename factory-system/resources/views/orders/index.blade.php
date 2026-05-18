@extends('layouts.app')
@section('title', __('ui.modules.orders'))
@section('page-title', __('ui.modules.orders'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('ui.modules.orders')" :description="__('orders.orders_description')">
    @can('create', App\Models\Order::class)
        <x-btn :href="route('orders.create')">{{ __('orders.create') }}</x-btn>
    @endcan
</x-page-header>
<x-card>
    <x-filter-panel :action="route('orders.index')" :reset="route('orders.index')">
        <x-form-input name="search" :label="__('ui.actions.search')" :value="request('search')" placeholder="{{ __('portal.order_number') }} / {{ __('ui.fields.customer') }}" />
        <x-form-select name="status" :label="__('ui.fields.status')">
            <option value="">{{ __('ui.labels.all_statuses') }}</option>
            @foreach(['pending','accepted','preparing','ready','shipped','delivered','cancelled','returned'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('ui.status.'.$s) }}</option>
            @endforeach
        </x-form-select>
        <x-form-input name="date_from" :label="__('ui.fields.from')" type="date" :value="request('date_from')" />
        <x-form-input name="date_to" :label="__('ui.fields.to')" type="date" :value="request('date_to')" />
    </x-filter-panel>

    <div class="table-scroll"><table class="table">
    <thead>
        <tr>
            <th scope="col">{{ __('portal.order_number') }}</th>
            <th scope="col">{{ __('ui.fields.customer') }}</th>
            <th scope="col">{{ __('ui.fields.date') }}</th>
            <th scope="col">{{ __('ui.fields.status') }}</th>
            <th scope="col">{{ __('ui.fields.total') }}</th>
            <th scope="col" class="table-actions">{{ __('ui.labels.actions') }}</th>
        </tr>
    </thead>
    <tbody>
    @forelse($orders as $order)
        <tr>
            <td class="font-bold text-slate-900">{{ $order->order_number }}</td>
            <td>{{ $order->customer?->name }}</td>
            <td>{{ $order->order_date?->format('Y-m-d') }}</td>
            <td><x-status-badge :status="$order->status" /></td>
            <td class="font-bold tabular-nums">{{ $money($order->total_amount) }}</td>
            <td class="table-actions">
                <a href="{{ route('orders.show', $order) }}" class="action-link" aria-label="{{ __('ui.actions.show') }} {{ $order->order_number }}">{{ __('ui.actions.show') }}</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="6"><x-empty-state /></td></tr>
    @endforelse
    </tbody></table></div>
    <x-pagination :paginator="$orders" />
</x-card>
@endsection
