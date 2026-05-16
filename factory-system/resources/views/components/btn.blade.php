@props(['href' => null, 'variant' => 'primary', 'size' => 'md', 'type' => 'button'])
@php($classes = trim('btn btn-'.$variant.' '.($size !== 'md' ? 'btn-'.$size : '')))

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
