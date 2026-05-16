@extends('layouts.app')

@section('title', __('ui.error_404'))

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center" dir="rtl">
    <div class="text-8xl font-bold text-brand-200 mb-4">404</div>
    <h1 class="text-2xl font-cairo font-bold text-ink-900 mb-2">{{ __('ui.page_not_found') }}</h1>
    <p class="text-ink-500 mb-8 max-w-md">{{ __('ui.page_not_found_message') }}</p>
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition font-cairo">
        <svg class="w-5 h-5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        {{ __('ui.back_to_dashboard') }}
    </a>
</div>
@endsection
