<div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8 no-print" aria-live="polite">
    @foreach([
        'success' => 'border-green-200 bg-green-50 text-green-800',
        'error' => 'border-red-200 bg-red-50 text-red-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
    ] as $key => $classes)
        @if(session($key))
            <div data-flash-dismiss class="mb-3 rounded-2xl border px-4 py-3 text-sm font-semibold {{ $classes }}">
                {{ session($key) }}
            </div>
        @endif
    @endforeach

    @if($errors->any())
        <div class="mb-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">{{ __('ui.messages.validation_failed') }}</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
