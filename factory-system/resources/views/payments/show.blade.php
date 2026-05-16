@extends('layouts.app')
@section('title', $payment->payment_number)
@section('page-title', __('payments.payment'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="$payment->payment_number" :description="$payment->customer?->name" :back="route('payments.index')" />
<x-card :title="__('payments.payment')"><dl class="grid gap-4 sm:grid-cols-3"><div><dt class="text-xs text-slate-500">{{ __('payments.amount') }}</dt><dd class="font-bold tabular-nums">{{ $money($payment->amount) }}</dd></div><div><dt class="text-xs text-slate-500">{{ __('payments.method') }}</dt><dd>{{ $payment->method_label }}</dd></div><div><dt class="text-xs text-slate-500">{{ __('payments.date') }}</dt><dd>{{ $payment->payment_date?->format('Y-m-d') }}</dd></div><div><dt class="text-xs text-slate-500">{{ __('payments.invoice') }}</dt><dd><a class="text-brand-700 font-bold" href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice?->invoice_number }}</a></dd></div><div><dt class="text-xs text-slate-500">{{ __('payments.received_by') }}</dt><dd>{{ $payment->receivedByUser?->name }}</dd></div></dl></x-card>
@endsection
