@props(['title' => null, 'message' => null])
<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
    <p class="text-base font-bold text-slate-700">{{ $title ?? __('ui.messages.empty_title') }}</p>
    <p class="mt-2 text-sm text-slate-500">{{ $message ?? __('ui.messages.empty_body') }}</p>
    @if($slot->isNotEmpty())
        <div class="mt-5 flex justify-center">{{ $slot }}</div>
    @endif
</div>
