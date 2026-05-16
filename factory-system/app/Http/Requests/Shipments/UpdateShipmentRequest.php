<?php

namespace App\Http\Requests\Shipments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('shipments.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'truck_id' => ['sometimes', 'required', 'integer', 'exists:trucks,id'],
            'driver_id' => ['sometimes', 'required', 'integer', 'exists:drivers,id'],
            'shipment_date' => ['sometimes', 'required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'truck_id' => __('shipments.truck'),
            'driver_id' => __('shipments.driver'),
            'shipment_date' => __('shipments.shipment_date'),
            'notes' => __('shipments.notes'),
        ];
    }
}
