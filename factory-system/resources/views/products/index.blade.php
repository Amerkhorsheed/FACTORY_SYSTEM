@extends('layouts.app')
@section('title', __('ui.modules.inventory'))
@section('page-title', __('ui.modules.inventory'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('ui.modules.inventory')" :description="__('products.catalog_description')">
    <x-btn :href="route('products.create')">{{ __('ui.actions.create') }}</x-btn>
</x-page-header>

<div class="mb-5 grid gap-4 md:grid-cols-3">
    <x-metric-card :label="__('products.low_stock')" :value="$lowCount" tone="amber" />
</div>

<x-card>
    <form method="GET" action="{{ route('products.index') }}" class="mb-5 grid gap-3 md:grid-cols-4">
        <x-form-input name="search" :label="__('ui.actions.search')" :value="request('search')" />
        <x-form-select name="category_id" :label="__('ui.fields.category')">
            <option value="">{{ __('ui.actions.search') }}</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </x-form-select>
        <div class="flex items-end"><x-btn type="submit">{{ __('ui.actions.search') }}</x-btn></div>
    </form>
    <div class="table-scroll"><table class="table">
        <thead><tr><th>{{ __('ui.fields.code') }}</th><th>{{ __('ui.fields.name') }}</th><th>{{ __('ui.fields.category') }}</th><th>{{ __('ui.fields.quantity') }}</th><th>{{ __('ui.fields.unit_price') }}</th><th></th></tr></thead>
        <tbody>
        @forelse($products as $product)
            <tr>
                <td class="font-mono text-xs">{{ $product->code }}</td>
                <td class="font-bold">{{ $product->name }}</td>
                <td>{{ $product->category?->name }}</td>
                <td class="font-bold tabular-nums">{{ $product->stock_quantity }} {{ $product->unit }}</td>
                <td class="tabular-nums">{{ $money($product->unit_price) }}</td>
                <td><a href="{{ route('products.show', $product) }}" class="font-bold text-brand-700">{{ __('ui.actions.show') }}</a></td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$products" />
</x-card>
@endsection
