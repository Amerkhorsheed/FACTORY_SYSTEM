@extends('layouts.app')
@section('title', __('ui.modules.stock_movements'))
@section('page-title', __('ui.modules.stock_movements'))

@section('content')
<x-page-header :title="__('ui.modules.stock_movements')" />
<x-card>
    <x-filter-panel :action="route('stock-movements.index')" :reset="route('stock-movements.index')">
        <x-form-input name="product_id" :label="__('ui.modules.inventory')" type="number" :value="request('product_id')" />
        <x-form-select name="type" :label="__('ui.fields.status')">
            <option value="">{{ __('ui.labels.all') }}</option>
            @foreach(['in', 'out', 'adjustment', 'return'] as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('stock_movements.types.'.$type) }}</option>
            @endforeach
        </x-form-select>
        <x-form-input name="date_from" :label="__('ui.fields.from')" type="date" :value="request('date_from')" />
        <x-form-input name="date_to" :label="__('ui.fields.to')" type="date" :value="request('date_to')" />
    </x-filter-panel>

    <div class="table-scroll"><table class="table">
        <thead>
            <tr>
                <th scope="col">{{ __('ui.fields.date') }}</th>
                <th scope="col">{{ __('ui.modules.inventory') }}</th>
                <th scope="col">{{ __('ui.fields.status') }}</th>
                <th scope="col">{{ __('ui.fields.quantity') }}</th>
                <th scope="col">{{ __('ui.fields.balance') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($movements as $movement)
            <tr>
                <td>{{ $movement->created_at?->format('Y-m-d') }}</td>
                <td class="font-bold text-slate-900">{{ $movement->product?->name }}</td>
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
