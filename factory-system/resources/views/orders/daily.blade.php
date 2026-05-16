@extends('layouts.app')
@section('title', __('orders.daily'))
@section('page-title', __('orders.daily'))

@section('content')
<x-page-header :title="__('orders.daily')" :description="$date->format('Y-m-d')" :back="route('orders.index')" />
<div class="grid gap-5 lg:grid-cols-2">
@forelse($orders as $status => $group)
    <x-card :title="__('ui.status.'.$status)">
        @foreach($group as $order)
            <a href="{{ route('orders.show', $order) }}" class="flex justify-between border-b border-slate-100 py-3 last:border-0"><span class="font-bold">{{ $order->order_number }}</span><span>{{ $order->customer?->name }}</span></a>
        @endforeach
    </x-card>
@empty
    <div class="lg:col-span-2"><x-empty-state /></div>
@endforelse
</div>
@endsection
