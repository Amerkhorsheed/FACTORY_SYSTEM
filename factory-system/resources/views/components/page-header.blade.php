@props(['title', 'description' => null, 'back' => null])
<div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        @if($back)
            <a href="{{ $back }}" class="mb-2 inline-flex rounded-lg px-2 py-1 text-sm font-bold text-brand-700 transition hover:bg-brand-50 hover:text-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-500">{{ __('ui.actions.back') }}</a>
        @endif
        <h2 class="text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ $title }}</h2>
        @if($description)
            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">{{ $description }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
