@props([
    'action' => url()->current(),
    'method' => 'GET',
    'title' => __('ui.labels.filters'),
    'description' => __('ui.messages.filters_description'),
    'reset' => null,
    'submit' => __('ui.actions.search'),
])

@php
    $activeFilters = collect(request()->query())
        ->except('page')
        ->filter(fn ($value) => is_array($value) ? count(array_filter($value, fn ($item) => filled($item))) > 0 : filled($value))
        ->count();
@endphp

<form method="{{ $method }}" action="{{ $action }}" {{ $attributes->merge(['class' => 'filter-panel']) }}>
    <div class="filter-panel-header">
        <div>
            <h3 class="filter-panel-title">{{ $title }}</h3>
            @if($description)
                <p class="filter-panel-description">{{ $description }}</p>
            @endif
        </div>
        @if($activeFilters > 0)
            <span class="filter-count">{{ __('ui.labels.active_filters', ['count' => $activeFilters]) }}</span>
        @endif
    </div>

    <div class="filter-panel-grid">
        {{ $slot }}
    </div>

    <div class="filter-panel-actions">
        <x-btn type="submit" class="w-full sm:w-auto sm:min-w-32">{{ $submit }}</x-btn>
        @if($reset)
            <a href="{{ $reset }}" class="btn btn-secondary w-full sm:w-auto">{{ __('ui.actions.reset') }}</a>
        @endif
    </div>
</form>
