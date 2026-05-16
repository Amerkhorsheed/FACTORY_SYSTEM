<form action="{{ $route }}" method="POST">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif
    <div class="mb-3">
        <label for="truck_id">{{ __('shipments.truck') }}</label>
        <select name="truck_id" id="truck_id" class="form-control" required>
            <option value="">--</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="driver_id">{{ __('shipments.driver') }}</label>
        <select name="driver_id" id="driver_id" class="form-control" required>
            <option value="">--</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="shipment_date">{{ __('shipments.shipment_date') }}</label>
        <input type="date" name="shipment_date" id="shipment_date" class="form-control" value="{{ old('shipment_date', $shipment->shipment_date ?? today()->format('Y-m-d')) }}" required>
    </div>
    <div class="mb-3">
        <label for="notes">{{ __('shipments.notes') }}</label>
        <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $shipment->notes ?? '') }}</textarea>
    </div>
    <button type="submit" class="btn btn-primary">{{ __('shipments.save') ?? 'حفظ' }}</button>
</form>
