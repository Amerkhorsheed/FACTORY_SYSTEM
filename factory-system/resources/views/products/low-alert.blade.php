@extends('layouts.app')
@section('title', __('products.low_stock'))
@section('page-title', __('products.low_stock'))

@section('content')
<x-page-header :title="__('products.low_stock')" :back="route('products.index')" />
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse($products as $product)
        <x-card :title="$product->name">
            <p class="text-sm text-slate-500">{{ $product->code }}</p>
            <p class="mt-3 text-2xl font-black text-amber-700 tabular-nums">{{ $product->stock_quantity }} / {{ $product->low_stock_threshold }}</p>
            <x-btn :href="route('products.show', $product)" variant="secondary" size="sm" class="mt-4">{{ __('ui.actions.show') }}</x-btn>
        </x-card>
    @empty
        <div class="md:col-span-2 xl:col-span-3"><x-empty-state /></div>
    @endforelse
</div>
@endsection
