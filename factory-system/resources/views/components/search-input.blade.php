@props(['name', 'value' => '', 'placeholder' => 'بحث...', 'debounce' => null])

<div class="relative rounded-md shadow-sm">
    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <svg class="w-5 h-5 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
    <input 
        type="text" 
        name="{{ $name }}" 
        id="{{ $name }}" 
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        @if($debounce) wire:model.live.debounce.{{ $debounce }}="{{ $name }}" @endif
        {{ $attributes->merge(['class' => 'block w-full pr-10 border-ink-300 rounded-md focus:ring-brand-500 focus:border-brand-500 sm:text-sm font-cairo transition-shadow']) }}
    >
</div>
