@extends('layouts.app')
@section('title', __('shipments.shipments'))
@section('page-title', __('shipments.shipments'))

@section('content')
<x-page-header :title="__('shipments.shipments')">
    @can('create', App\Models\Shipment::class)
        <x-btn :href="route('shipments.create')">{{ __('shipments.create') }}</x-btn>
    @endcan
</x-page-header>
<x-card>
    <x-filter-panel :action="route('shipments.index')" :reset="route('shipments.index')">
        <x-form-select name="status" :label="__('ui.fields.status')">
            <option value="">{{ __('ui.labels.all_statuses') }}</option>
            @foreach(['planned','loading','dispatched','completed','cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('ui.status.'.$s) }}</option>
            @endforeach
        </x-form-select>
        <x-form-input name="date" :label="__('ui.fields.date')" type="date" :value="request('date')" />
        <x-form-input name="truck_id" :label="__('shipments.truck')" type="number" :value="request('truck_id')" />
        <x-form-input name="driver_id" :label="__('shipments.driver')" type="number" :value="request('driver_id')" />
    </x-filter-panel>

    <div class="table-scroll"><table class="table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">{{ __('shipments.truck') }}</th>
                <th scope="col">{{ __('shipments.driver') }}</th>
                <th scope="col">{{ __('shipments.shipment_date') }}</th>
                <th scope="col">{{ __('ui.fields.status') }}</th>
                <th scope="col">{{ __('shipments.orders') }}</th>
                <th scope="col" class="table-actions">{{ __('ui.labels.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($shipments as $shipment)
            <tr>
                <td class="font-bold text-slate-900">{{ $shipment->shipment_number }}</td>
                <td>{{ $shipment->truck?->plate_number }}</td>
                <td>{{ $shipment->driver?->name }}</td>
                <td>{{ $shipment->shipment_date?->format('Y-m-d') }}</td>
                <td><x-status-badge :status="$shipment->status" /></td>
                <td>{{ $shipment->orders->count() }}</td>
                <td class="table-actions">
                    <a href="{{ route('shipments.show', $shipment) }}" class="action-link" aria-label="{{ __('ui.actions.show') }} {{ $shipment->shipment_number }}">{{ __('ui.actions.show') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$shipments" />
</x-card>
@endsection
