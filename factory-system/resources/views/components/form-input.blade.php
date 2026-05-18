@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'id' => null, 'helper' => null])
@php
    $inputId = $id ?? str_replace(['.', '[', ']'], '-', $name);
    $hasError = $errors->has($name);
    $errorId = $inputId.'-error';
    $helperId = $helper ? $inputId.'-helper' : null;
    $describedBy = trim(($helperId ?? '').' '.($hasError ? $errorId : ''));
    $classes = trim('form-input '.($hasError ? 'form-input-invalid' : ''));
@endphp
<div>
    @if($label)
        <label for="{{ $inputId }}" class="form-label">{{ $label }} @if($required)<span class="text-red-600">*</span>@endif</label>
    @endif
    <input id="{{ $inputId }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" {{ $required ? 'required' : '' }} @if($hasError) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    @if($helper)<p id="{{ $helperId }}" class="form-helper">{{ $helper }}</p>@endif
    @error($name)<p id="{{ $errorId }}" class="form-error">{{ $message }}</p>@enderror
</div>
