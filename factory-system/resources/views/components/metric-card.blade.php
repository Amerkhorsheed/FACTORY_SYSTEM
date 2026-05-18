@props(['label', 'value', 'hint' => null, 'tone' => 'brand'])
@php($tones = ['brand' => 'bg-brand-50 text-brand-700', 'green' => 'bg-green-50 text-green-700', 'red' => 'bg-red-50 text-red-700', 'amber' => 'bg-amber-50 text-amber-700'])
<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <p class="text-sm font-bold text-slate-500">{{ $label }}</p>
    <p class="mt-3 inline-flex rounded-2xl px-3 py-2 text-2xl font-black tabular-nums {{ $tones[$tone] ?? $tones['brand'] }}">{{ $value }}</p>
    @if($hint)
        <p class="mt-3 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
