@extends('layouts.app')
@section('title', __('shipments.shipments'))
@section('page-title', __('shipments.shipments'))

@section('content')
<x-page-header :title="__('shipments.shipments')"><x-btn :href="route('shipments.create')">{{ __('shipments.create') }}</x-btn></x-page-header>
<x-card>
    <form method="GET" action="{{ route('shipments.index') }}" class="mb-5 grid gap-3 md:grid-cols-5"><x-form-select name="status" :label="__('ui.fields.status')"><option value="">{{ __('ui.actions.search') }}</option>@foreach(['planned','loading','dispatched','completed','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __('ui.status.'.$s) }}</option>@endforeach</x-form-select><x-form-input name="date" :label="__('ui.fields.date')" type="date" :value="request('date')" /><x-form-input name="truck_id" :label="__('shipments.truck')" type="number" :value="request('truck_id')" /><x-form-input name="driver_id" :label="__('shipments.driver')" type="number" :value="request('driver_id')" /><div class="flex items-end"><x-btn type="submit">{{ __('ui.actions.search') }}</x-btn></div></form>
    <div class="table-scroll"><table class="table"><thead><tr><th>#</th><th>{{ __('shipments.truck') }}</th><th>{{ __('shipments.driver') }}</th><th>{{ __('shipments.shipment_date') }}</th><th>{{ __('ui.fields.status') }}</th><th>{{ __('shipments.orders') }}</th><th></th></tr></thead><tbody>@forelse($shipments as $shipment)<tr><td class="font-bold">{{ $shipment->shipment_number }}</td><td>{{ $shipment->truck?->plate_number }}</td><td>{{ $shipment->driver?->name }}</td><td>{{ $shipment->shipment_date?->format('Y-m-d') }}</td><td><x-status-badge :status="$shipment->status" /></td><td>{{ $shipment->orders->count() }}</td><td><a href="{{ route('shipments.show', $shipment) }}" class="font-bold text-brand-700">{{ __('ui.actions.show') }}</a></td></tr>@empty<tr><td colspan="7"><x-empty-state /></td></tr>@endforelse</tbody></table></div><x-pagination :paginator="$shipments" />
</x-card>
@endsection
