@props(['name', 'label' => null, 'rows' => 3])
<div>
    @if($label)
        <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->merge(['class' => 'form-input']) }}>{{ old($name, $slot) }}</textarea>
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
