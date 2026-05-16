@extends('layouts.app')
@section('title', __('orders.edit'))
@section('page-title', __('ui.modules.orders'))

@section('content')
<x-page-header :title="__('orders.edit')" :description="$order->order_number" :back="route('orders.show', $order)" />
<form method="POST" action="{{ route('orders.update', $order) }}" class="max-w-5xl">@method('PUT') @include('orders._form')</form>
@endsection
