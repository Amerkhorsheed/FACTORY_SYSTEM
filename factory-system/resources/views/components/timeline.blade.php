@props(['steps' => [], 'current' => null])

@php
    $currentIndex = array_search($current, array_keys($steps));
@endphp

<div class="py-6 overflow-x-auto">
    <nav aria-label="Progress">
        <ol role="list" class="flex items-center">
            @foreach($steps as $key => $label)
                @php
                    $loopIndex = $loop->index;
                    $isCompleted = $currentIndex !== false && $loopIndex < $currentIndex;
                    $isCurrent = $currentIndex !== false && $loopIndex === $currentIndex;
                @endphp

                <li class="relative {{ !$loop->last ? 'pr-8 sm:pr-20' : '' }}">
                    @if(!$loop->last)
                        <!-- Connecting line -->
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="h-0.5 w-full {{ $isCompleted ? 'bg-brand-600' : 'bg-ink-200' }}"></div>
                        </div>
                    @endif

                    <div class="relative flex items-center justify-center w-8 h-8 rounded-full {{ $isCompleted ? 'bg-brand-600 hover:bg-brand-900' : ($isCurrent ? 'bg-white border-2 border-brand-600' : 'bg-white border-2 border-ink-300') }}">
                        @if($isCompleted)
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @elseif($isCurrent)
                            <span class="w-2.5 h-2.5 bg-brand-600 rounded-full" aria-hidden="true"></span>
                        @endif
                    </div>
                    
                    <div class="absolute -bottom-6 right-1/2 transform translate-x-1/2 flex items-center justify-center text-xs font-bold font-cairo whitespace-nowrap {{ $isCompleted || $isCurrent ? 'text-brand-600' : 'text-ink-500' }}">
                        {{ $label }}
                    </div>
                </li>
            @endforeach
        </ol>
    </nav>
</div>
