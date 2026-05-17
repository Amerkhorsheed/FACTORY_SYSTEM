<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('ui.labels.factory_system')) - {{ __('ui.labels.factory_system') }}</title>
    @unless(app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
</head>
<body class="font-cairo bg-slate-950 text-white antialiased" dir="rtl">
    @php
        $dashboardUrl = auth()->check()
            ? (auth()->user()->hasRole('customer') ? route('portal.dashboard') : route('erp.dashboard'))
            : route('login');
    @endphp

    <div class="relative min-h-screen overflow-hidden bg-slate-950">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(37,99,235,0.32),_transparent_34rem),radial-gradient(circle_at_bottom_left,_rgba(14,165,233,0.18),_transparent_28rem)]"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-l from-transparent via-white/40 to-transparent"></div>

        <header class="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-lg font-bold text-brand-700 shadow-lg shadow-brand-950/30">
                    {{ mb_substr(__('ui.labels.factory_system'), 0, 1) }}
                </span>
                <span>
                    <span class="block text-sm font-bold tracking-tight text-white">{{ __('ui.labels.factory_system') }}</span>
                    <span class="block text-xs text-slate-300">{{ __('welcome.header_subtitle') }}</span>
                </span>
            </a>

            <a href="{{ $dashboardUrl }}" class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/70">
                {{ auth()->check() ? __('welcome.actions.dashboard') : __('auth.login') }}
            </a>
        </header>

        <main class="relative z-10">
            @yield('content')
        </main>

        <footer class="relative z-10 border-t border-white/10 px-4 py-6 text-center text-xs text-slate-400">
            {{ __('welcome.footer_note', ['year' => date('Y')]) }}
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
