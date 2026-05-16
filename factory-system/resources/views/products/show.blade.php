@extends('layouts.app')
@section('title', $product->name)
@section('page-title', __('ui.modules.inventory'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="$product->name" :description="$product->code" :back="route('products.index')">
    <x-btn :href="route('products.edit', $product)" variant="secondary">{{ __('ui.actions.edit') }}</x-btn>
</x-page-header>

<div class="grid gap-6 lg:grid-cols-3">
    <x-card :title="__('products.product_details')" class="lg:col-span-2">
        <dl class="grid gap-4 sm:grid-cols-3">
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.category') }}</dt><dd class="font-bold">{{ $product->category?->name }}</dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.quantity') }}</dt><dd class="font-bold tabular-nums">{{ $product->stock_quantity }} {{ $product->unit }}</dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.status') }}</dt><dd><x-status-badge :status="$product->is_active ? 'active' : 'inactive'" /></dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.unit_price') }}</dt><dd class="font-bold tabular-nums">{{ $money($product->unit_price) }}</dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.cost_price') }}</dt><dd class="font-bold tabular-nums">{{ $money($product->cost_price) }}</dd></div>
            <div><dt class="text-xs text-slate-500">{{ __('ui.fields.barcode') }}</dt><dd>{{ $product->barcode ?? '-' }}</dd></div>
        </dl>
    </x-card>
    <x-card :title="__('products.stock_adjustment')">
        <form method="POST" action="{{ route('stock-adjustments.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <x-form-input name="new_quantity" :label="__('ui.fields.quantity')" type="number" :value="$product->stock_quantity" required />
            <x-form-textarea name="reason" :label="__('ui.fields.notes')" rows="3" />
            <x-btn type="submit" class="w-full">{{ __('products.adjust_stock') }}</x-btn>
        </form>
    </x-card>
</div>

<x-card :title="__('ui.modules.stock_movements')" class="mt-6">
    @forelse($movements as $movement)
        <div class="flex items-center justify-between border-b border-slate-100 py-3 last:border-0">
            <div><p class="font-bold">{{ $movement->type_label }}</p><p class="text-xs text-slate-500">{{ $movement->created_at?->format('Y-m-d') }}</p></div>
            <p class="font-bold tabular-nums">{{ $movement->quantity_after }}</p>
        </div>
    @empty
        <x-empty-state />
    @endforelse
</x-card>
@endsection
