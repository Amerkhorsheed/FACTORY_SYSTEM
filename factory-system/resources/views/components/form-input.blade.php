@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false])
<div>
    @if($label)
        <label for="{{ $name }}" class="form-label">{{ $label }} @if($required)<span class="text-red-600">*</span>@endif</label>
    @endif
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" {{ $required ? 'required' : '' }} {{ $attributes->merge(['class' => 'form-input']) }}>
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
