@extends('layouts.public')

@section('title', __('welcome.title'))

@section('content')
    <section class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.08fr_0.92fr] lg:px-8 lg:py-20">
        <div class="space-y-8">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold text-sky-100 backdrop-blur">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                {{ __('welcome.hero_badge') }}
            </div>

            <div class="max-w-3xl space-y-5">
                <h1 class="text-4xl font-bold leading-tight text-white sm:text-6xl">{{ __('welcome.hero_title') }}</h1>
                <p class="text-lg leading-8 text-slate-300">{{ __('welcome.hero_body') }}</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('login') }}" class="btn btn-primary btn-lg">{{ __('welcome.actions.login') }}</a>
                <a href="#capabilities" class="btn btn-secondary btn-lg border-white/20 bg-white/10 text-white hover:bg-white/15">{{ __('welcome.actions.learn') }}</a>
            </div>

            <dl class="grid gap-4 sm:grid-cols-3">
                @foreach(__('welcome.stats') as $stat)
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur">
                        <dt class="text-sm font-semibold text-slate-300">{{ $stat['label'] }}</dt>
                        <dd class="mt-2 text-3xl font-bold text-white">{{ $stat['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="rounded-[2rem] border border-white/15 bg-white p-4 text-slate-900 shadow-2xl shadow-slate-950/30 sm:p-6">
            <div class="rounded-[1.5rem] bg-slate-950 p-5 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-sky-300">{{ __('welcome.cockpit.badge') }}</p>
                        <h2 class="mt-2 text-2xl font-bold">{{ __('welcome.cockpit.title') }}</h2>
                    </div>
                    <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-bold text-emerald-200">{{ __('welcome.cockpit.status') }}</span>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach(__('welcome.cockpit.metrics') as $metric)
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-xs text-slate-300">{{ $metric['label'] }}</p>
                            <p class="mt-2 text-2xl font-bold">{{ $metric['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 space-y-3">
                @foreach(__('welcome.cockpit.pipeline') as $item)
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <span class="text-sm font-bold text-slate-800">{{ $item['label'] }}</span>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">{{ $item['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="capabilities" class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8 lg:pb-24">
        <div class="mb-8 max-w-3xl">
            <p class="text-sm font-bold text-sky-200">{{ __('welcome.capabilities_badge') }}</p>
            <h2 class="mt-3 text-3xl font-bold text-white">{{ __('welcome.capabilities_title') }}</h2>
            <p class="mt-3 leading-7 text-slate-300">{{ __('welcome.capabilities_body') }}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            @foreach(__('welcome.features') as $feature)
                <article class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur transition hover:-translate-y-1 hover:bg-white/15">
                    <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-lg font-bold text-brand-700">{{ $feature['number'] }}</div>
                    <h3 class="text-lg font-bold text-white">{{ $feature['title'] }}</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-300">{{ $feature['body'] }}</p>
                </article>
            @endforeach
        </div>
    </section>
@endsection
