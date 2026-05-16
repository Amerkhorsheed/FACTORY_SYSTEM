@csrf
<x-card>
    <div class="grid gap-4 md:grid-cols-3">
        <x-form-input name="customer_id" :label="__('ui.fields.customer')" type="number" :value="$order->customer_id ?? null" required />
        <x-form-input name="order_date" :label="__('ui.fields.date')" type="date" :value="isset($order) ? $order->order_date?->format('Y-m-d') : today()->toDateString()" required />
        <x-form-input name="requested_delivery_date" :label="__('portal.requested_delivery_date')" type="date" :value="isset($order) ? $order->requested_delivery_date?->format('Y-m-d') : null" />
    </div>
    @php($item = isset($order) ? $order->items->first() : null)
    <div class="mt-5 grid gap-4 md:grid-cols-4">
        <x-form-input name="items[0][product_id]" :label="__('portal.product')" type="number" :value="$item?->product_id" required />
        <x-form-input name="items[0][quantity]" :label="__('ui.fields.quantity')" type="number" :value="$item?->quantity ?? 1" required />
        <x-form-input name="items[0][unit_price]" :label="__('ui.fields.unit_price')" type="number" :value="$item?->unit_price ?? 0" required />
        <x-form-input name="items[0][discount_percent]" :label="__('ui.fields.discount')" type="number" :value="$item?->discount_percent ?? 0" />
    </div>
    <div class="mt-4"><x-form-textarea name="notes" :label="__('ui.fields.notes')">{{ $order->notes ?? '' }}</x-form-textarea></div>
    <x-btn type="submit" class="mt-5">{{ __('ui.actions.save') }}</x-btn>
</x-card>
