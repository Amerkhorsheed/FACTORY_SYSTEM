@extends('layouts.app')
@section('title', __('ui.modules.customers'))
@section('page-title', __('ui.modules.customers'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('ui.modules.customers')" :description="__('customers.customers_description')">
    @can('create', App\Models\Customer::class)
        <x-btn :href="route('customers.create')">{{ __('ui.actions.create') }}</x-btn>
    @endcan
</x-page-header>
<div class="mb-5 grid gap-4 md:grid-cols-3">
    <x-metric-card :label="__('portal.total_orders')" :value="$kpis['total'] ?? 0" />
    <x-metric-card label="A" :value="$kpis['category_a'] ?? 0" tone="green" />
    <x-metric-card :label="__('portal.outstanding_balance')" :value="$kpis['with_debt'] ?? 0" tone="red" />
</div>
<x-card>
    <x-filter-panel :action="route('customers.index')" :reset="route('customers.index')">
        <x-form-input name="search" :label="__('ui.actions.search')" :value="request('search')" placeholder="{{ __('ui.fields.name') }} / {{ __('ui.fields.code') }} / {{ __('ui.fields.phone') }}" />
        <x-form-input name="region" :label="__('ui.fields.region')" :value="request('region')" />
        <x-form-select name="category" :label="__('ui.fields.category')">
            <option value="">{{ __('ui.labels.all') }}</option>
            @foreach(['A','B','C'] as $c)
                <option value="{{ $c }}" @selected(request('category') === $c)>{{ $c }}</option>
            @endforeach
        </x-form-select>
    </x-filter-panel>

    <div class="table-scroll"><table class="table">
        <thead>
            <tr>
                <th scope="col">{{ __('ui.fields.code') }}</th>
                <th scope="col">{{ __('ui.fields.name') }}</th>
                <th scope="col">{{ __('ui.fields.phone') }}</th>
                <th scope="col">{{ __('ui.fields.category') }}</th>
                <th scope="col">{{ __('ui.fields.balance') }}</th>
                <th scope="col" class="table-actions">{{ __('ui.labels.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($customers as $customer)
            <tr>
                <td class="font-mono text-xs">{{ $customer->code }}</td>
                <td class="font-bold text-slate-900">{{ $customer->name }}</td>
                <td>{{ $customer->phone }}</td>
                <td>{{ $customer->category }}</td>
                <td class="font-bold tabular-nums">{{ $money($customer->outstanding_balance) }}</td>
                <td class="table-actions">
                    <a href="{{ route('customers.show', $customer) }}" class="action-link" aria-label="{{ __('ui.actions.show') }} {{ $customer->name }}">{{ __('ui.actions.show') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$customers" />
</x-card>
@endsection
