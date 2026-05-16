@extends('layouts.app')
@section('title', __('expenses.expense'))
@section('page-title', __('expenses.expense'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="$expense->category" :description="$expense->expense_date?->format('Y-m-d')" :back="route('erp.expenses.index')"><x-btn :href="route('erp.expenses.edit', $expense)" variant="secondary">{{ __('ui.actions.edit') }}</x-btn></x-page-header>
<x-card :title="__('expenses.expense')"><dl class="grid gap-4 sm:grid-cols-3"><div><dt class="text-xs text-slate-500">{{ __('expenses.amount') }}</dt><dd class="font-bold tabular-nums">{{ $money($expense->amount) }}</dd></div><div><dt class="text-xs text-slate-500">{{ __('expenses.reference') }}</dt><dd>{{ $expense->reference ?? '-' }}</dd></div><div><dt class="text-xs text-slate-500">{{ __('payments.received_by') }}</dt><dd>{{ $expense->createdByUser?->name }}</dd></div><div class="sm:col-span-3"><dt class="text-xs text-slate-500">{{ __('expenses.description') }}</dt><dd>{{ $expense->description }}</dd></div></dl></x-card>
@endsection
