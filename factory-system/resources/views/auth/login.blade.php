@extends('layouts.public')

@section('title', __('auth.login'))

@section('content')
    <section class="mx-auto grid min-h-[calc(100vh-9rem)] max-w-7xl items-center gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8 lg:py-16">
        <div class="order-2 lg:order-1">
            <div class="overflow-hidden rounded-[2rem] border border-white/15 bg-white text-slate-900 shadow-2xl shadow-slate-950/30">
                <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-5 sm:px-8">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">{{ __('auth.access_badge') }}</p>
                            <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ __('auth.login') }}</h1>
                        </div>
                        <div class="hidden rounded-2xl bg-brand-50 px-4 py-3 text-center sm:block">
                            <span class="block text-xs font-semibold text-brand-700">{{ __('auth.secure_access_label') }}</span>
                            <span class="block text-lg font-bold text-brand-900">{{ __('auth.secure_access_value') }}</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 px-6 py-7 sm:px-8">
                    @if($errors->any())
                        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if(session('status'))
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label class="form-label" for="login">{{ __('auth.fields.login') }}</label>
                            <input
                                id="login"
                                name="login"
                                type="text"
                                value="{{ old('login') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="{{ __('auth.placeholders.login') }}"
                                class="form-input @error('login') border-red-500 @enderror"
                            >
                            @error('login')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="form-label" for="password">{{ __('auth.fields.password') }}</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="{{ __('auth.placeholders.password') }}"
                                class="form-input @error('password') border-red-500 @enderror"
                            >
                            @error('password')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex flex-col gap-3 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
                            <label class="inline-flex cursor-pointer items-center gap-2 font-semibold">
                                <input name="remember" type="checkbox" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span>{{ __('auth.remember_me') }}</span>
                            </label>
                            <span>{{ __('auth.rate_limit_hint') }}</span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-full">
                            {{ __('auth.login_button') }}
                        </button>
                    </form>

                    <p class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm leading-7 text-slate-600">
                        {{ __('auth.secure_access_note') }}
                    </p>
                </div>
            </div>
        </div>

        <aside class="order-1 space-y-8 lg:order-2">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold text-sky-100 backdrop-blur">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                {{ __('auth.hero_badge') }}
            </div>

            <div class="max-w-2xl space-y-5">
                <h2 class="text-4xl font-bold leading-tight text-white sm:text-5xl">{{ __('auth.hero_title') }}</h2>
                <p class="text-lg leading-8 text-slate-300">{{ __('auth.hero_body') }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                @foreach(__('auth.assurances') as $assurance)
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur">
                        <p class="text-2xl font-bold text-white">{{ $assurance['value'] }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">{{ $assurance['label'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-slate-900/70 p-5 shadow-2xl shadow-slate-950/20 backdrop-blur">
                <h3 class="text-base font-bold text-white">{{ __('auth.flow_title') }}</h3>
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    @foreach(__('auth.flow_steps') as $step)
                        <div class="rounded-2xl bg-white/5 p-4">
                            <span class="text-xs font-bold text-sky-200">{{ $step['number'] }}</span>
                            <p class="mt-2 text-sm font-semibold leading-6 text-white">{{ $step['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </section>
@endsection
