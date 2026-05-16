@props(['name', 'value' => '', 'label' => null, 'required' => false])

<div class="form-group">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-ink-700 mb-1 font-cairo">
            {{ $label }} @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    
    <div class="relative rounded-md shadow-sm">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <span class="text-ink-500 sm:text-sm font-cairo">
                {{ config('factory.currency') === 'SYP' ? 'ل.س' : config('factory.currency') }}
            </span>
        </div>
        <input 
            type="number" 
            name="{{ $name }}" 
            id="{{ $name }}" 
            value="{{ $value }}"
            step="1"
            min="0"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge(['class' => 'block w-full pl-12 pr-3 border-ink-300 rounded-md focus:ring-brand-500 focus:border-brand-500 sm:text-sm transition-shadow']) }}
        >
    </div>
    
    @error($name)
        <p class="mt-2 text-sm text-red-600 font-cairo" id="{{ $name }}-error">{{ $message }}</p>
    @enderror
</div>
