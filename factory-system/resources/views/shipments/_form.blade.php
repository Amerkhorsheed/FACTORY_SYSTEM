@csrf
<x-card>
    <div class="grid gap-4 md:grid-cols-3">
        <x-form-select name="truck_id" :label="__('shipments.truck')" required>@foreach($trucks as $truck)<option value="{{ $truck->id }}" @selected(old('truck_id', $shipment->truck_id ?? '') == $truck->id)>{{ $truck->plate_number }}</option>@endforeach</x-form-select>
        <x-form-select name="driver_id" :label="__('shipments.driver')" required>@foreach($drivers as $driver)<option value="{{ $driver->id }}" @selected(old('driver_id', $shipment->driver_id ?? '') == $driver->id)>{{ $driver->name }}</option>@endforeach</x-form-select>
        <x-form-input name="shipment_date" :label="__('shipments.shipment_date')" type="date" :value="isset($shipment) ? $shipment->shipment_date?->format('Y-m-d') : today()->toDateString()" required />
    </div>
    <div class="mt-4"><x-form-textarea name="notes" :label="__('ui.fields.notes')">{{ $shipment->notes ?? '' }}</x-form-textarea></div>
    <x-btn type="submit" class="mt-5">{{ __('ui.actions.save') }}</x-btn>
</x-card>
