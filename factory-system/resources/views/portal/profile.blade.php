@extends('layouts.app')
@section('title', __('ui.modules.profile'))
@section('page-title', __('ui.modules.profile'))

@section('content')
<x-page-header :title="__('portal.business_profile')" :description="$customer->name" />

<form method="POST" action="{{ route('portal.profile.update') }}" class="max-w-3xl">
    @csrf @method('PUT')
    <x-card :title="__('portal.business_profile')">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form-input name="phone" :label="__('portal.phone')" :value="$customer->phone" required />
            <x-form-input name="phone_alt" :label="__('portal.phone_alt')" :value="$customer->phone_alt" />
        </div>
        <div class="mt-4">
            <x-form-textarea name="address" :label="__('portal.address')" rows="4">{{ $customer->address }}</x-form-textarea>
        </div>
        <x-btn type="submit" class="mt-5">{{ __('ui.actions.save') }}</x-btn>
    </x-card>
</form>
@endsection
