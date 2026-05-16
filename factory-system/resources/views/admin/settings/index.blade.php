@extends('layouts.app')
@section('title', __('admin.settings'))
@section('page-title', __('admin.settings'))

@section('content')
<x-page-header :title="__('admin.settings')" />

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-2">
    @csrf
    <x-card :title="__('admin.factory_name')">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form-input name="factory_name" :label="__('admin.factory_name')" :value="$settings['factory_name'] ?? ''" required />
            <x-form-input name="factory_phone" :label="__('admin.factory_phone')" :value="$settings['factory_phone'] ?? ''" />
            <x-form-input name="factory_address" :label="__('admin.factory_address')" :value="$settings['factory_address'] ?? ''" />
            <x-form-input name="factory_tax_number" :label="__('admin.factory_tax_number')" :value="$settings['factory_tax_number'] ?? ''" />
            <x-form-input name="factory_logo" :label="__('admin.factory_logo')" type="file" />
        </div>
    </x-card>

    <x-card :title="__('ui.modules.invoices')">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-form-input name="invoice_prefix" :label="__('admin.invoice_prefix')" :value="$settings['invoice_prefix'] ?? 'INV'" required />
            <x-form-input name="invoice_due_days" :label="__('admin.invoice_due_days')" type="number" :value="$settings['invoice_due_days'] ?? 30" required />
            <x-form-input name="invoice_tax_rate" :label="__('admin.invoice_tax_rate')" type="number" :value="$settings['invoice_tax_rate'] ?? 0" required />
        </div>
        <div class="mt-4 grid gap-4">
            <x-form-textarea name="invoice_footer_text" :label="__('admin.invoice_footer_text')">{{ $settings['invoice_footer_text'] ?? '' }}</x-form-textarea>
            <x-form-textarea name="invoice_bank_details" :label="__('admin.invoice_bank_details')">{{ $settings['invoice_bank_details'] ?? '' }}</x-form-textarea>
            <x-form-textarea name="invoice_terms" :label="__('admin.invoice_terms')">{{ $settings['invoice_terms'] ?? '' }}</x-form-textarea>
        </div>
    </x-card>

    <x-card :title="__('ui.modules.inventory')">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form-input name="default_low_threshold" :label="__('admin.default_low_threshold')" type="number" :value="$settings['default_low_threshold'] ?? 10" required />
            <label class="flex items-center gap-2 pt-7 text-sm font-semibold text-slate-700">
                <input type="hidden" name="enable_stock_warnings" value="0">
                <input type="checkbox" name="enable_stock_warnings" value="1" @checked($settings['enable_stock_warnings'] ?? false) class="rounded border-slate-300 text-brand-600">
                {{ __('admin.enable_stock_warnings') }}
            </label>
        </div>
    </x-card>

    <x-card :title="__('ui.modules.customers')">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form-input name="default_credit_limit" :label="__('admin.default_credit_limit')" type="number" :value="$settings['default_credit_limit'] ?? 0" required />
            <x-form-select name="default_category" :label="__('admin.default_category')" required>
                @foreach(['A', 'B', 'C'] as $category)
                    <option value="{{ $category }}" @selected(($settings['default_category'] ?? 'B') === $category)>{{ $category }}</option>
                @endforeach
            </x-form-select>
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="hidden" name="enable_arabic_numerals" value="0">
                <input type="checkbox" name="enable_arabic_numerals" value="1" @checked($settings['enable_arabic_numerals'] ?? false) class="rounded border-slate-300 text-brand-600">
                {{ __('admin.enable_arabic_numerals') }}
            </label>
        </div>
        <x-btn type="submit" class="mt-6">{{ __('admin.save') }}</x-btn>
    </x-card>
</form>
@endsection
