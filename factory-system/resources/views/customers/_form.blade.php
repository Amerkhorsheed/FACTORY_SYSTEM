@csrf
<x-card>
    <div class="grid gap-4 md:grid-cols-3">
        <x-form-input name="name" :label="__('ui.fields.name')" :value="$customer->name ?? null" required />
        <x-form-input name="business_name" label="Business" :value="$customer->business_name ?? null" />
        <x-form-input name="phone" :label="__('ui.fields.phone')" :value="$customer->phone ?? null" required />
        <x-form-input name="phone_alt" label="Alt" :value="$customer->phone_alt ?? null" />
        <x-form-input name="email" :label="__('ui.fields.email')" type="email" :value="$customer->email ?? null" />
        <x-form-select name="category" :label="__('ui.fields.category')" required>@foreach(['A','B','C'] as $c)<option value="{{ $c }}" @selected(old('category', $customer->category ?? 'B') === $c)>{{ $c }}</option>@endforeach</x-form-select>
        <x-form-input name="city" :label="__('ui.fields.city')" :value="$customer->city ?? null" />
        <x-form-input name="region" :label="__('ui.fields.region')" :value="$customer->region ?? null" />
        <x-form-input name="credit_limit" :label="__('portal.available_credit')" type="number" :value="$customer->credit_limit ?? 0" required />
    </div>
    <div class="mt-4 grid gap-4"><x-form-textarea name="address" :label="__('ui.fields.address')">{{ $customer->address ?? '' }}</x-form-textarea><x-form-textarea name="notes" :label="__('ui.fields.notes')">{{ $customer->notes ?? '' }}</x-form-textarea></div>
    @empty($customer)<label class="mt-4 flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="portal_access" value="1" class="rounded border-slate-300 text-brand-600"> {{ __('ui.modules.profile') }}</label><x-form-input name="portal_password" :label="__('admin.password')" type="password" class="mt-3" />@endempty
    <x-btn type="submit" class="mt-5">{{ __('ui.actions.save') }}</x-btn>
</x-card>
