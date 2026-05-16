@extends('layouts.app')
@section('title', __('erp.profit_loss_report'))
@section('page-title', __('erp.profit_loss_report'))

@section('content')
@php($money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp'))
<x-page-header :title="__('erp.profit_loss_report')" />
<div class="grid gap-4 md:grid-cols-3"><x-metric-card :label="__('erp.revenue')" :value="$money($revenue)" tone="green" /><x-metric-card :label="__('erp.expenses')" :value="$money($expenses)" tone="red" /><x-metric-card :label="__('erp.profit')" :value="$money($profit)" :tone="$profit >= 0 ? 'brand' : 'red'" /></div>
@endsection
