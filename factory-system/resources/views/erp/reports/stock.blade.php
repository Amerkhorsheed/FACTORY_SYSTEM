@extends('layouts.app')
@section('title', __('erp.stock_report'))
@section('page-title', __('erp.stock_report'))

@section('content')
<x-page-header :title="__('erp.stock_report')" />
<div class="grid gap-6 lg:grid-cols-3"><x-card :title="__('ui.modules.stock_movements')" class="lg:col-span-2"><div class="table-scroll"><table class="table"><thead><tr><th>{{ __('ui.fields.date') }}</th><th>{{ __('portal.product') }}</th><th>{{ __('ui.fields.status') }}</th><th>{{ __('ui.fields.quantity') }}</th></tr></thead><tbody>@forelse($movements as $movement)<tr><td>{{ $movement->created_at?->format('Y-m-d') }}</td><td>{{ $movement->product?->name }}</td><td>{{ $movement->type_label }}</td><td>{{ $movement->quantity }}</td></tr>@empty<tr><td colspan="4"><x-empty-state /></td></tr>@endforelse</tbody></table></div><x-pagination :paginator="$movements" /></x-card><x-card :title="__('products.low_stock')">@forelse($lowStock as $product)<div class="border-b border-slate-100 py-2 last:border-0"><p class="font-bold">{{ $product->name }}</p><p class="text-sm text-amber-700">{{ $product->stock_quantity }} / {{ $product->low_stock_threshold }}</p></div>@empty<x-empty-state />@endforelse</x-card></div>
@endsection
