@extends('layouts.app')
@section('title', $customer->name)
@section('page-title', __('ui.modules.customers'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="$customer->name" :description="$customer->code" :back="route('customers.index')">
    <x-btn :href="route('customers.edit', $customer)" variant="secondary">{{ __('ui.actions.edit') }}</x-btn>
    <x-btn :href="route('customers.statement', $customer)" variant="secondary">{{ __('customers.statement') }}</x-btn>
</x-page-header>
<div class="grid gap-6 lg:grid-cols-3">
    <x-card :title="__('customers.customer_details')" class="lg:col-span-2">
        <dl class="grid gap-4 sm:grid-cols-3">
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.phone') }}</dt><dd class="font-bold">{{ $customer->phone }}</dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.email') }}</dt><dd>{{ $customer->email ?? '-' }}</dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.category') }}</dt><dd class="font-bold">{{ $customer->category }}</dd></div>
            <div class="sm:col-span-3"><dt class="text-xs text-slate-500">{{ __('ui.fields.address') }}</dt><dd>{{ $customer->address }}</dd></div>
        </dl>
    </x-card>
    <x-card :title="__('portal.outstanding_balance')">
        <p class="text-2xl font-black text-red-700 tabular-nums">{{ $money($customer->outstanding_balance) }}</p>
        <p class="mt-2 text-sm text-slate-500">{{ __('portal.available_credit') }}: {{ $money($customer->available_credit) }}</p>
    </x-card>
</div>
<x-card :title="__('ui.modules.orders')" class="mt-6">
    @forelse($customer->orders as $order)
        <a href="{{ route('orders.show', $order) }}" class="flex items-center justify-between border-b border-slate-100 py-3 last:border-0"><span class="font-bold">{{ $order->order_number }}</span><x-status-badge :status="$order->status" /></a>
    @empty
        <x-empty-state />
    @endforelse
</x-card>
@endsection
