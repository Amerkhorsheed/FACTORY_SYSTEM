@extends('layouts.app')
@section('title', __('ui.modules.customers'))
@section('page-title', __('ui.modules.customers'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('ui.modules.customers')" :description="__('customers.customers_description')">
    <x-btn :href="route('customers.create')">{{ __('ui.actions.create') }}</x-btn>
</x-page-header>
<div class="mb-5 grid gap-4 md:grid-cols-3">
    <x-metric-card :label="__('portal.total_orders')" :value="$kpis['total'] ?? 0" />
    <x-metric-card label="A" :value="$kpis['category_a'] ?? 0" tone="green" />
    <x-metric-card :label="__('portal.outstanding_balance')" :value="$kpis['with_debt'] ?? 0" tone="red" />
</div>
<x-card>
    <form method="GET" action="{{ route('customers.index') }}" class="mb-5 grid gap-3 md:grid-cols-5">
        <x-form-input name="search" :label="__('ui.actions.search')" :value="request('search')" />
        <x-form-input name="region" :label="__('ui.fields.region')" :value="request('region')" />
        <x-form-select name="category" :label="__('ui.fields.category')"><option value="">{{ __('ui.actions.search') }}</option>@foreach(['A','B','C'] as $c)<option value="{{ $c }}" @selected(request('category') === $c)>{{ $c }}</option>@endforeach</x-form-select>
        <div class="flex items-end"><x-btn type="submit">{{ __('ui.actions.search') }}</x-btn></div>
    </form>
    <div class="table-scroll"><table class="table">
        <thead><tr><th>{{ __('ui.fields.code') }}</th><th>{{ __('ui.fields.name') }}</th><th>{{ __('ui.fields.phone') }}</th><th>{{ __('ui.fields.category') }}</th><th>{{ __('ui.fields.balance') }}</th><th></th></tr></thead>
        <tbody>
        @forelse($customers as $customer)
            <tr><td class="font-mono text-xs">{{ $customer->code }}</td><td class="font-bold">{{ $customer->name }}</td><td>{{ $customer->phone }}</td><td>{{ $customer->category }}</td><td class="font-bold tabular-nums">{{ $money($customer->outstanding_balance) }}</td><td><a href="{{ route('customers.show', $customer) }}" class="font-bold text-brand-700">{{ __('ui.actions.show') }}</a></td></tr>
        @empty
            <tr><td colspan="6"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$customers" />
</x-card>
@endsection
