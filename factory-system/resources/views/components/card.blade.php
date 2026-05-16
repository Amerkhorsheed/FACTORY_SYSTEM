@props(['title' => null])
<section {{ $attributes->merge(['class' => 'card']) }}>
    @if($title)
        <header class="card-header">{{ $title }}</header>
    @endif
    <div class="card-body">{{ $slot }}</div>
</section>
