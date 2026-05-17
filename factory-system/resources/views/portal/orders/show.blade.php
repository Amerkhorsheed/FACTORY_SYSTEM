@extends('layouts.app')
@section('title', __('portal.order_details'))
@section('page-title', __('portal.order_details'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="$order->order_number" :description="__('portal.order_details')" :back="route('portal.orders.index')" />

<div class="grid gap-6 lg:grid-cols-3">
    <x-card :title="__('portal.order_details')" class="lg:col-span-2">
        <div class="grid gap-4 sm:grid-cols-3">
            <div><p class="text-xs text-slate-500">{{ __('portal.order_date') }}</p><p class="font-bold">{{ $order->order_date?->format('Y-m-d') }}</p></div>
            <div><p class="text-xs text-slate-500">{{ __('portal.status') }}</p><x-status-badge :status="$order->status" /></div>
            <div><p class="text-xs text-slate-500">{{ __('portal.total') }}</p><p class="font-bold tabular-nums">{{ $money($order->total_amount) }}</p></div>
        </div>
    </x-card>

    <x-card :title="__('portal.payment_status')">
        @if($order->invoice)
            <p class="font-bold">{{ $order->invoice->invoice_number }}</p>
            <x-status-badge :status="$order->invoice->status" class="mt-3" />
            <p class="mt-3 text-sm font-bold tabular-nums">{{ $money($order->invoice->balance_due) }}</p>
        @else
            <x-empty-state />
        @endif
    </x-card>
</div>

{{-- Visual Timeline --}}
<x-card :title="__('portal.order_tracking')" class="mt-6">
    <div class="relative">
        @if($timeline['isCancelled'])
            <div class="mb-4 rounded-lg bg-rose-50 p-4 text-rose-700">
                <p class="font-semibold">{{ __('portal.order_cancelled') }}</p>
                @if($timeline['cancelReason'])
                    <p class="mt-1 text-sm">{{ $timeline['cancelReason'] }}</p>
                @endif
            </div>
        @elseif($timeline['isReturned'])
            <div class="mb-4 rounded-lg bg-amber-50 p-4 text-amber-700">
                <p class="font-semibold">{{ __('portal.order_returned') }}</p>
            </div>
        @endif

        <div class="flex justify-between">
            @foreach($timeline['steps'] as $index => $step)
                <?php
                    $stepKey = $step['key'];
                    $stepIndex = array_search($stepKey, $timeline['statusOrder']);
                    $isCompleted = $timeline['isCancelled'] || $timeline['isReturned'] ? false : ($timeline['currentIndex'] !== false && $stepIndex <= $timeline['currentIndex']);
                    $isCurrent = ! $timeline['isCancelled'] && ! $timeline['isReturned'] && $timeline['currentIndex'] !== false && $stepIndex === $timeline['currentIndex'];

                    if ($isCompleted) {
                        $circleClass = 'bg-emerald-500 text-white ring-4 ring-emerald-100';
                    } elseif ($isCurrent) {
                        $circleClass = 'bg-primary-600 text-white ring-4 ring-primary-200 animate-pulse';
                    } else {
                        $circleClass = 'bg-gray-200 text-gray-400';
                    }
                ?>

                <div class="flex flex-1 flex-col items-center">
                    {{-- Step Circle --}}
                    <div class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full transition-all duration-500 {{ $circleClass }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/>
                        </svg>
                    </div>

                    {{-- Step Label --}}
                    <p class="mt-2 text-center text-xs font-medium {{ $isCompleted || $isCurrent ? 'text-gray-900' : 'text-gray-400' }}">
                        {{ __('portal.status_'.$stepKey) }}
                    </p>

                    {{-- Timestamp --}}
                    @if($isCompleted && $stepKey !== 'pending')
                        <p class="mt-0.5 text-center text-[10px] text-gray-400">
                            <?php
                                $dateFields = ['accepted' => 'accepted_at', 'shipped' => 'shipped_at', 'delivered' => 'delivered_at'];
                                $dateField = $dateFields[$stepKey] ?? null;
                            ?>
                            {{ $dateField && $order->$dateField ? $order->$dateField->format('Y-m-d') : '' }}
                        </p>
                    @endif

                    {{-- Connector Line (except last) --}}
                    @if($index < count($timeline['steps']) - 1)
                        <div class="absolute top-5 hidden h-0.5 w-full lg:block" style="left: {{ (($index + 0.5) / count($timeline['steps'])) * 100 }}%; width: {{ (1 / count($timeline['steps'])) * 100 }}%;">
                            <div class="h-full rounded-full {{ $isCompleted ? 'bg-emerald-500' : 'bg-gray-200' }}"></div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-card>

<x-card :title="__('portal.product')" class="mt-6">
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>{{ __('portal.product') }}</th><th>{{ __('portal.quantity') }}</th><th>{{ __('portal.total') }}</th></tr></thead>
            <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }} {{ $item->product->unit }}</td>
                    <td class="font-bold tabular-nums">{{ $money($item->line_total) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@endsection
