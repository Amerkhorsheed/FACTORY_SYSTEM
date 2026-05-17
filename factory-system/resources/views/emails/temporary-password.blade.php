@extends('emails.layout')

@section('content')
    <h2>{{ __('notifications.temporary_password.subject') }}</h2>
    <p>{{ __('notifications.temporary_password.body') }}</p>

    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin: 20px 0; padding: 16px; text-align: center;">
        <p style="margin: 0 0 8px; color: #475569;">{{ __('notifications.temporary_password.password') }}</p>
        <p style="direction: ltr; font-size: 22px; font-weight: bold; letter-spacing: 2px; margin: 0;">{{ $temporary_password }}</p>
    </div>

    <p>{{ __('notifications.temporary_password.warning') }}</p>

    <div style="text-align: center;">
        <a href="{{ $login_url }}" class="btn">{{ __('notifications.actions.sign_in') }}</a>
    </div>
@endsection
