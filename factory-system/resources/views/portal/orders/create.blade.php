@extends('layouts.app')
@section('title', __('portal.create_order'))
@section('page-title', __('portal.create_order'))

@section('content')
<x-page-header :title="__('portal.create_order')" :description="__('portal.request_order_description')" :back="route('portal.orders.index')" />

<form method="POST" action="{{ route('portal.orders.store') }}" class="grid gap-6 lg:grid-cols-3">
    @csrf
    <x-card :title="__('portal.product')" class="lg:col-span-2">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <x-form-select name="items[0][product_id]" :label="__('portal.product')" required data-tom-select>
                    <option value="">{{ __('ui.actions.search') }}</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} - {{ number_format($product->unit_price) }}</option>
                    @endforeach
                </x-form-select>
            </div>
            <x-form-input name="items[0][quantity]" :label="__('portal.quantity')" type="number" value="1" required min="1" />
        </div>
        <div class="mt-4">
            <x-form-textarea name="items[0][notes]" :label="__('portal.notes')" rows="2" />
        </div>
    </x-card>

    <x-card :title="__('portal.order_details')">
        <x-form-input name="requested_delivery_date" :label="__('portal.requested_delivery_date')" type="date" data-datepicker />
        <div class="mt-4">
            <x-form-textarea name="notes" :label="__('portal.notes')" rows="4" />
        </div>
        <x-btn type="submit" class="mt-5 w-full">{{ __('ui.actions.submit') }}</x-btn>
    </x-card>
</form>
@endsection
