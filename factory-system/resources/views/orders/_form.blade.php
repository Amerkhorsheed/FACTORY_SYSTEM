@csrf
@php
    $initialItems = isset($order)
        ? $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'discount_percent' => $item->discount_percent,
            'notes' => $item->notes,
        ])->values()
        : collect([['product_id' => '', 'quantity' => 1, 'discount_percent' => 0, 'notes' => '']]);
@endphp
<x-card x-data="{ items: @js($initialItems) }">
    <div class="grid gap-4 md:grid-cols-3">
        <x-form-select name="customer_id" :label="__('ui.fields.customer')" required data-tom-select>
            <option value="">{{ __('ui.actions.search') }}</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected((int) old('customer_id', $order->customer_id ?? 0) === $customer->id)>{{ $customer->code }} - {{ $customer->name }}</option>
            @endforeach
        </x-form-select>
        <x-form-input name="order_date" :label="__('ui.fields.date')" type="date" :value="isset($order) ? $order->order_date?->format('Y-m-d') : today()->toDateString()" required />
        <x-form-input name="requested_delivery_date" :label="__('portal.requested_delivery_date')" type="date" :value="isset($order) ? $order->requested_delivery_date?->format('Y-m-d') : null" />
    </div>

    <div class="mt-5 space-y-3">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-bold text-slate-700">{{ __('orders.items') }}</h3>
            <x-btn type="button" variant="secondary" size="sm" @click="items.push({ product_id: '', quantity: 1, discount_percent: 0, notes: '' })">{{ __('orders.add_item') }}</x-btn>
        </div>

        <template x-for="(item, index) in items" :key="index">
            <div class="grid gap-3 rounded-2xl border border-slate-200 p-4 md:grid-cols-[2fr_1fr_1fr_auto]">
                <div>
                    <label class="form-label">{{ __('portal.product') }} <span class="text-red-600">*</span></label>
                    <select class="form-input" :name="`items[${index}][product_id]`" x-model="item.product_id" required>
                        <option value="">{{ __('ui.actions.search') }}</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->code }} - {{ $product->name }} ({{ $product->stock_quantity }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ __('ui.fields.quantity') }} <span class="text-red-600">*</span></label>
                    <input class="form-input" type="number" min="1" :name="`items[${index}][quantity]`" x-model="item.quantity" required>
                </div>
                <div>
                    <label class="form-label">{{ __('ui.fields.discount') }}</label>
                    <input class="form-input" type="number" min="0" max="100" step="0.01" :name="`items[${index}][discount_percent]`" x-model="item.discount_percent">
                </div>
                <div class="flex items-end">
                    <x-btn type="button" variant="ghost" size="sm" @click="items.splice(index, 1)" x-show="items.length > 1">{{ __('ui.actions.delete') }}</x-btn>
                </div>
            </div>
        </template>
    </div>
    <div class="mt-4"><x-form-textarea name="notes" :label="__('ui.fields.notes')">{{ $order->notes ?? '' }}</x-form-textarea></div>
    <x-btn type="submit" class="mt-5">{{ __('ui.actions.save') }}</x-btn>
</x-card>
