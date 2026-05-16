<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>{{ __('admin.settings') }}</title></head>
<body>
<h1>{{ __('admin.settings') }}</h1>
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
    @csrf
    <label>{{ __('admin.factory_name') }} <input name="factory_name" value="{{ old('factory_name', $settings['factory_name'] ?? '') }}" required></label>
    <label>{{ __('admin.factory_address') }} <input name="factory_address" value="{{ old('factory_address', $settings['factory_address'] ?? '') }}"></label>
    <label>{{ __('admin.factory_phone') }} <input name="factory_phone" value="{{ old('factory_phone', $settings['factory_phone'] ?? '') }}"></label>
    <label>{{ __('admin.factory_tax_number') }} <input name="factory_tax_number" value="{{ old('factory_tax_number', $settings['factory_tax_number'] ?? '') }}"></label>
    <label>{{ __('admin.invoice_prefix') }} <input name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}" required></label>
    <label>{{ __('admin.invoice_due_days') }} <input name="invoice_due_days" type="number" value="{{ old('invoice_due_days', $settings['invoice_due_days'] ?? 30) }}" required></label>
    <label>{{ __('admin.invoice_tax_rate') }} <input name="invoice_tax_rate" type="number" value="{{ old('invoice_tax_rate', $settings['invoice_tax_rate'] ?? 0) }}" required></label>
    <label>{{ __('admin.invoice_footer_text') }} <textarea name="invoice_footer_text">{{ old('invoice_footer_text', $settings['invoice_footer_text'] ?? '') }}</textarea></label>
    <label>{{ __('admin.invoice_bank_details') }} <textarea name="invoice_bank_details">{{ old('invoice_bank_details', $settings['invoice_bank_details'] ?? '') }}</textarea></label>
    <label>{{ __('admin.invoice_terms') }} <textarea name="invoice_terms">{{ old('invoice_terms', $settings['invoice_terms'] ?? '') }}</textarea></label>
    <label>{{ __('admin.default_low_threshold') }} <input name="default_low_threshold" type="number" value="{{ old('default_low_threshold', $settings['default_low_threshold'] ?? 10) }}" required></label>
    <input type="hidden" name="enable_stock_warnings" value="0">
    <label><input name="enable_stock_warnings" type="checkbox" value="1" @checked(old('enable_stock_warnings', $settings['enable_stock_warnings'] ?? false))> {{ __('admin.enable_stock_warnings') }}</label>
    <label>{{ __('admin.default_credit_limit') }} <input name="default_credit_limit" type="number" value="{{ old('default_credit_limit', $settings['default_credit_limit'] ?? 0) }}" required></label>
    <label>{{ __('admin.default_category') }}
        <select name="default_category">
            @foreach(['A', 'B', 'C'] as $category)
                <option value="{{ $category }}" @selected(old('default_category', $settings['default_category'] ?? 'B') === $category)>{{ $category }}</option>
            @endforeach
        </select>
    </label>
    <input type="hidden" name="enable_arabic_numerals" value="0">
    <label><input name="enable_arabic_numerals" type="checkbox" value="1" @checked(old('enable_arabic_numerals', $settings['enable_arabic_numerals'] ?? false))> {{ __('admin.enable_arabic_numerals') }}</label>
    <label>{{ __('admin.factory_logo') }} <input name="factory_logo" type="file"></label>
    <button type="submit">{{ __('admin.save') }}</button>
</form>
</body>
</html>
