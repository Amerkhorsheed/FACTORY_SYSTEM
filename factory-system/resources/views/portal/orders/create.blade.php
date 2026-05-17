@extends('layouts.app')
@section('title', __('portal.create_order'))
@section('page-title', __('portal.create_order'))

@section('content')
<x-page-header :title="__('portal.create_order')" :description="__('portal.request_order_description')" :back="route('portal.orders.index')" />

<livewire:portal.order-cart :customer="$customer" />
@endsection

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('notify', ({ type, message }) => {
            window.dispatchEvent(new CustomEvent('notify', { detail: { type, message } }));
        });

        Livewire.on('orderCreated', ({ orderId }) => {
            window.location.href = `{{ url('portal/orders') }}/${orderId}`;
        });
    });
</script>
@endpush
