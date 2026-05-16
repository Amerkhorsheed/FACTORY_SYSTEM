<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('ui.modules.dashboard')) - {{ config('app.name') }}</title>
    @unless(app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
</head>
<body class="font-cairo bg-slate-50 text-slate-900" dir="rtl" x-data>
    @include('layouts.partials.sidebar')

    <div class="min-h-screen lg:mr-sidebar">
        @include('layouts.partials.topbar')
        @include('layouts.partials.alerts')

        <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 py-5 text-center text-xs text-slate-500">
            {{ config('app.name') }} - {{ date('Y') }}
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
