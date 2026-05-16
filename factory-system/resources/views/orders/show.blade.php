@extends('layouts.app')
@section('title', $order->order_number)
@section('page-title', __('ui.modules.orders'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="$order->order_number" :description="$order->customer?->name" :back="route('orders.index')">@if($order->isEditable())<x-btn :href="route('orders.edit', $order)" variant="secondary">{{ __('ui.actions.edit') }}</x-btn>@endif</x-page-header>
<div class="grid gap-6 lg:grid-cols-3"><x-card :title="__('portal.order_details')" class="lg:col-span-2"><dl class="grid gap-4 sm:grid-cols-3"><div><dt class="text-xs text-slate-500">{{ __('ui.fields.date') }}</dt><dd class="font-bold">{{ $order->order_date?->format('Y-m-d') }}</dd></div><div><dt class="text-xs text-slate-500">{{ __('ui.fields.status') }}</dt><dd><x-status-badge :status="$order->status" /></dd></div><div><dt class="text-xs text-slate-500">{{ __('ui.fields.total') }}</dt><dd class="font-bold tabular-nums">{{ $money($order->total_amount) }}</dd></div></dl></x-card><x-card :title="__('ui.modules.invoices')">@if($order->invoice)<a class="font-bold text-brand-700" href="{{ route('invoices.show', $order->invoice) }}">{{ $order->invoice->invoice_number }}</a><p class="mt-2"><x-status-badge :status="$order->invoice->status" /></p>@else<x-empty-state />@endif</x-card></div>
<x-card :title="__('portal.product')" class="mt-6"><div class="table-scroll"><table class="table"><thead><tr><th>{{ __('portal.product') }}</th><th>{{ __('ui.fields.quantity') }}</th><th>{{ __('ui.fields.unit_price') }}</th><th>{{ __('ui.fields.total') }}</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td>{{ $item->product?->name }}</td><td>{{ $item->quantity }}</td><td>{{ $money($item->unit_price) }}</td><td class="font-bold tabular-nums">{{ $money($item->line_total) }}</td></tr>@endforeach</tbody></table></div></x-card>
@endsection
