@props(['label', 'value', 'hint' => null, 'tone' => 'brand'])
@php($tones = ['brand' => 'bg-brand-50 text-brand-700', 'green' => 'bg-green-50 text-green-700', 'red' => 'bg-red-50 text-red-700', 'amber' => 'bg-amber-50 text-amber-700'])
<div class="card p-5">
    <p class="text-sm font-semibold text-slate-500">{{ $label }}</p>
    <p class="mt-3 text-2xl font-black tabular-nums {{ $tones[$tone] ?? $tones['brand'] }} rounded-xl px-3 py-2 inline-block">{{ $value }}</p>
    @if($hint)
        <p class="mt-3 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
