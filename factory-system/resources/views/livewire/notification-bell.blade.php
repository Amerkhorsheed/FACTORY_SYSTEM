<div class="relative" x-data="{ open: false }" wire:poll.30s="loadNotifications">
    <button @click="open = !open" @click.outside="open = false" class="relative p-2 text-ink-500 hover:text-brand-600 hover:bg-brand-50 rounded-full transition">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        
        @if($unreadCount > 0)
            <span class="absolute top-1 right-1 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute left-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-ink-200 z-50 overflow-hidden"
         style="display: none;" dir="rtl">
        
        <div class="flex items-center justify-between px-4 py-3 bg-ink-50 border-b border-ink-200">
            <h3 class="font-bold text-ink-900 font-cairo">{{ __('notifications.bell.title') }}</h3>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-brand-600 hover:text-brand-800 transition">
                    {{ __('notifications.actions.mark_all_read') }}
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <div class="flex items-start gap-3 p-4 border-b border-ink-100 hover:bg-ink-50 transition {{ $notification->read_at ? 'opacity-60' : 'bg-brand-50/30' }}">
                    <div class="flex-1 min-w-0">
                        @php($url = $notification->data['url'] ?? null)
                        @if($url)
                            <a href="{{ $url }}" wire:click="markAsRead('{{ $notification->id }}')" class="block text-sm font-bold text-ink-900 mb-1 hover:text-brand-700">
                                {{ $notification->data['message'] ?? __('notifications.bell.fallback') }}
                            </a>
                        @else
                            <p class="text-sm font-bold text-ink-900 mb-1">
                                {{ $notification->data['message'] ?? __('notifications.bell.fallback') }}
                            </p>
                        @endif
                        <p class="text-xs text-ink-500">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @if(!$notification->read_at)
                        <button wire:click="markAsRead('{{ $notification->id }}')" class="flex-shrink-0 text-brand-600 hover:text-brand-800" title="{{ __('notifications.actions.mark_read') }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-ink-500">
                    <svg class="w-12 h-12 mx-auto text-ink-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <p>{{ __('notifications.bell.empty') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
