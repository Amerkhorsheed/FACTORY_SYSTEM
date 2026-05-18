@props(['name', 'label' => null, 'required' => false, 'id' => null, 'helper' => null])
@php
    $selectId = $id ?? str_replace(['.', '[', ']'], '-', $name);
    $hasError = $errors->has($name);
    $errorId = $selectId.'-error';
    $helperId = $helper ? $selectId.'-helper' : null;
    $describedBy = trim(($helperId ?? '').' '.($hasError ? $errorId : ''));
    $classes = trim('form-input '.($hasError ? 'form-input-invalid' : ''));
@endphp
<div>
    @if($label)
        <label for="{{ $selectId }}" class="form-label">{{ $label }} @if($required)<span class="text-red-600">*</span>@endif</label>
    @endif
    <select id="{{ $selectId }}" name="{{ $name }}" {{ $required ? 'required' : '' }} @if($hasError) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</select>
    @if($helper)<p id="{{ $helperId }}" class="form-helper">{{ $helper }}</p>@endif
    @error($name)<p id="{{ $errorId }}" class="form-error">{{ $message }}</p>@enderror
</div>
