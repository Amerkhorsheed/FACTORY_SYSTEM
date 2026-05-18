@extends('layouts.app')
@section('title', __('ui.modules.inventory'))
@section('page-title', __('ui.modules.inventory'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('ui.modules.inventory')" :description="__('products.catalog_description')">
    @can('create', App\Models\Product::class)
        <x-btn :href="route('products.create')">{{ __('ui.actions.create') }}</x-btn>
    @endcan
</x-page-header>

<div class="mb-5 grid gap-4 md:grid-cols-3">
    <x-metric-card :label="__('products.low_stock')" :value="$lowCount" tone="amber" />
</div>

<x-card>
    <x-filter-panel :action="route('products.index')" :reset="route('products.index')">
        <x-form-input name="search" :label="__('ui.actions.search')" :value="request('search')" placeholder="{{ __('ui.fields.name') }} / {{ __('ui.fields.code') }}" />
        <x-form-select name="category_id" :label="__('ui.fields.category')">
            <option value="">{{ __('ui.labels.all') }}</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </x-form-select>
    </x-filter-panel>

    <div class="table-scroll"><table class="table">
        <thead>
            <tr>
                <th scope="col">{{ __('ui.fields.code') }}</th>
                <th scope="col">{{ __('ui.fields.name') }}</th>
                <th scope="col">{{ __('ui.fields.category') }}</th>
                <th scope="col">{{ __('ui.fields.quantity') }}</th>
                <th scope="col">{{ __('ui.fields.unit_price') }}</th>
                <th scope="col" class="table-actions">{{ __('ui.labels.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($products as $product)
            <tr>
                <td class="font-mono text-xs">{{ $product->code }}</td>
                <td class="font-bold text-slate-900">{{ $product->name }}</td>
                <td>{{ $product->category?->name }}</td>
                <td class="font-bold tabular-nums">{{ $product->stock_quantity }} {{ $product->unit }}</td>
                <td class="tabular-nums">{{ $money($product->unit_price) }}</td>
                <td class="table-actions">
                    <a href="{{ route('products.show', $product) }}" class="action-link" aria-label="{{ __('ui.actions.show') }} {{ $product->name }}">{{ __('ui.actions.show') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$products" />
</x-card>
@endsection
