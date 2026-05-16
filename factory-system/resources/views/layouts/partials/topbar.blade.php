<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur no-print">
    <div class="flex h-16 items-center justify-between px-4 sm:px-6">
        <div class="flex items-center gap-3">
            <button type="button" class="btn btn-ghost lg:hidden" @click="$store.sidebar.toggle()">
                <span class="sr-only">{{ __('ui.actions.menu') }}</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <div>
                <p class="text-xs font-semibold text-slate-400">{{ __('ui.labels.current_page') }}</p>
                <h1 class="text-sm font-bold text-slate-800 sm:text-base">@yield('page-title', __('ui.modules.dashboard'))</h1>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden text-left sm:block">
                <p class="text-sm font-bold text-slate-800">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500" dir="ltr">{{ auth()->user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">{{ __('auth.logout') }}</button>
            </form>
        </div>
    </div>
</header>
