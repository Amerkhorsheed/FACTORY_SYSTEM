@props(['name', 'label' => null, 'required' => false])
<div>
    @if($label)
        <label for="{{ $name }}" class="form-label">{{ $label }} @if($required)<span class="text-red-600">*</span>@endif</label>
    @endif
    <select id="{{ $name }}" name="{{ $name }}" {{ $required ? 'required' : '' }} {{ $attributes->merge(['class' => 'form-input']) }}>{{ $slot }}</select>
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
