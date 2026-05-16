@extends('layouts.app')
@section('title', __('ui.modules.stock_movements'))
@section('page-title', __('ui.modules.stock_movements'))

@section('content')
<x-page-header :title="__('ui.modules.stock_movements')" />
<x-card>
    <form method="GET" action="{{ route('stock-movements.index') }}" class="mb-5 grid gap-3 md:grid-cols-5">
        <x-form-input name="product_id" :label="__('ui.modules.inventory')" type="number" :value="request('product_id')" />
        <x-form-select name="type" :label="__('ui.fields.status')">
            <option value="">{{ __('ui.actions.search') }}</option>
            @foreach(['in', 'out', 'adjustment', 'return'] as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('stock_movements.types.'.$type) }}</option>
            @endforeach
        </x-form-select>
        <x-form-input name="date_from" :label="__('ui.fields.from')" type="date" :value="request('date_from')" />
        <x-form-input name="date_to" :label="__('ui.fields.to')" type="date" :value="request('date_to')" />
        <div class="flex items-end"><x-btn type="submit">{{ __('ui.actions.search') }}</x-btn></div>
    </form>

    <div class="table-scroll"><table class="table">
        <thead><tr><th>{{ __('ui.fields.date') }}</th><th>{{ __('ui.modules.inventory') }}</th><th>{{ __('ui.fields.status') }}</th><th>{{ __('ui.fields.quantity') }}</th><th>{{ __('ui.fields.balance') }}</th></tr></thead>
        <tbody>
        @forelse($movements as $movement)
            <tr>
                <td>{{ $movement->created_at?->format('Y-m-d') }}</td>
                <td class="font-bold">{{ $movement->product?->name }}</td>
                <td>{{ $movement->type_label }}</td>
                <td class="tabular-nums">{{ $movement->quantity }}</td>
                <td class="font-bold tabular-nums">{{ $movement->quantity_before }} -> {{ $movement->quantity_after }}</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$movements" />
</x-card>
@endsection
