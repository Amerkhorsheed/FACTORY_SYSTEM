@props(['title', 'description' => null, 'back' => null])
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        @if($back)
            <a href="{{ $back }}" class="mb-2 inline-block text-sm font-semibold text-brand-700">{{ __('ui.actions.back') }}</a>
        @endif
        <h2 class="text-2xl font-black tracking-tight text-slate-900">{{ $title }}</h2>
        @if($description)
            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">{{ $description }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
