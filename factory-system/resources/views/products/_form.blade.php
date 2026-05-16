@csrf
<x-card>
    <div class="grid gap-4 md:grid-cols-3">
        <x-form-input name="name" :label="__('ui.fields.name')" :value="$product->name ?? null" required />
        <x-form-input name="code" :label="__('ui.fields.code')" :value="$product->code ?? null" />
        <x-form-select name="category_id" :label="__('ui.fields.category')">
            <option value="">{{ __('ui.actions.search') }}</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </x-form-select>
        <x-form-input name="unit" :label="__('ui.fields.unit')" :value="$product->unit ?? 'قطعة'" required />
        <x-form-input name="unit_price" :label="__('ui.fields.unit_price')" type="number" :value="$product->unit_price ?? 0" required />
        <x-form-input name="cost_price" :label="__('ui.fields.cost_price')" type="number" :value="$product->cost_price ?? 0" required />
        <x-form-input name="stock_quantity" :label="__('ui.fields.quantity')" type="number" :value="$product->stock_quantity ?? 0" required />
        <x-form-input name="low_stock_threshold" :label="__('products.low_threshold')" type="number" :value="$product->low_stock_threshold ?? 10" required />
        <x-form-input name="barcode" :label="__('ui.fields.barcode')" :value="$product->barcode ?? null" />
        <x-form-input name="sort_order" label="Sort" type="number" :value="$product->sort_order ?? 0" />
        <x-form-input name="image" :label="__('ui.fields.image')" type="file" />
    </div>
    <div class="mt-4"><x-form-textarea name="description" :label="__('ui.fields.description')">{{ $product->description ?? '' }}</x-form-textarea></div>
    <input type="hidden" name="is_active" value="0">
    <label class="mt-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="rounded border-slate-300 text-brand-600">
        {{ __('ui.status.active') }}
    </label>
    <x-btn type="submit" class="mt-5">{{ __('ui.actions.save') }}</x-btn>
</x-card>
