@extends('layouts.app')
@section('title', __('erp.sales_report'))
@section('page-title', __('erp.sales_report'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('erp.sales_report')" />
<div class="mb-5 grid gap-4 md:grid-cols-3">
    <x-metric-card :label="__('ui.fields.total')" :value="$money($summary['total'] ?? 0)" />
    <x-metric-card :label="__('ui.modules.orders')" :value="$summary['count'] ?? 0" tone="green" />
    <x-metric-card :label="__('ui.status.delivered')" :value="$summary['delivered'] ?? 0" tone="amber" />
</div>
<x-card>
    <div class="table-scroll"><table class="table">
        <thead>
            <tr>
                <th scope="col">{{ __('portal.order_number') }}</th>
                <th scope="col">{{ __('ui.fields.customer') }}</th>
                <th scope="col">{{ __('ui.fields.date') }}</th>
                <th scope="col">{{ __('ui.fields.status') }}</th>
                <th scope="col">{{ __('ui.fields.total') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($orders as $order)
            <tr>
                <td class="font-bold text-slate-900">{{ $order->order_number }}</td>
                <td>{{ $order->customer?->name }}</td>
                <td>{{ $order->order_date?->format('Y-m-d') }}</td>
                <td><x-status-badge :status="$order->status" /></td>
                <td class="font-bold tabular-nums">{{ $money($order->total_amount) }}</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$orders" />
</x-card>
@endsection
