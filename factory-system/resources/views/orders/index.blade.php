@extends('layouts.app')
@section('title', __('ui.modules.orders'))
@section('page-title', __('ui.modules.orders'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('ui.modules.orders')" :description="__('orders.orders_description')"><x-btn :href="route('orders.create')">{{ __('orders.create') }}</x-btn></x-page-header>
<x-card>
    <form method="GET" action="{{ route('orders.index') }}" class="mb-5 grid gap-3 md:grid-cols-5">
        <x-form-input name="search" :label="__('ui.actions.search')" :value="request('search')" />
        <x-form-select name="status" :label="__('ui.fields.status')"><option value="">{{ __('ui.actions.search') }}</option>@foreach(['pending','accepted','preparing','ready','shipped','delivered','cancelled','returned'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __('ui.status.'.$s) }}</option>@endforeach</x-form-select>
        <x-form-input name="date_from" :label="__('ui.fields.from')" type="date" :value="request('date_from')" />
        <x-form-input name="date_to" :label="__('ui.fields.to')" type="date" :value="request('date_to')" />
        <div class="flex items-end"><x-btn type="submit">{{ __('ui.actions.search') }}</x-btn></div>
    </form>
    <div class="table-scroll"><table class="table"><thead><tr><th>{{ __('portal.order_number') }}</th><th>{{ __('ui.fields.customer') }}</th><th>{{ __('ui.fields.date') }}</th><th>{{ __('ui.fields.status') }}</th><th>{{ __('ui.fields.total') }}</th><th></th></tr></thead><tbody>
    @forelse($orders as $order)
        <tr><td class="font-bold">{{ $order->order_number }}</td><td>{{ $order->customer?->name }}</td><td>{{ $order->order_date?->format('Y-m-d') }}</td><td><x-status-badge :status="$order->status" /></td><td class="font-bold tabular-nums">{{ $money($order->total_amount) }}</td><td><a href="{{ route('orders.show', $order) }}" class="font-bold text-brand-700">{{ __('ui.actions.show') }}</a></td></tr>
    @empty
        <tr><td colspan="6"><x-empty-state /></td></tr>
    @endforelse
    </tbody></table></div><x-pagination :paginator="$orders" />
</x-card>
@endsection
