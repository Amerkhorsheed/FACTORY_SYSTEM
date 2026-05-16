@props(['status'])
@php
    $palette = match ($status) {
        'paid', 'delivered', 'active' => 'bg-green-50 text-green-700 ring-green-200',
        'partial', 'ready', 'issued' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'pending', 'preparing', 'draft' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'cancelled', 'returned', 'void', 'inactive' => 'bg-red-50 text-red-700 ring-red-200',
        default => 'bg-slate-50 text-slate-700 ring-slate-200',
    };
    $key = 'ui.status.'.$status;
    $label = __($key) === $key ? $status : __($key);
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 '.$palette]) }}>{{ $label }}</span>
