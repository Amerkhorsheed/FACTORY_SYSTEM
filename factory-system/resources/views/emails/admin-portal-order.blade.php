@extends('emails.layout')

@section('content')
    <h2>{{ __('notifications.portal_order.subject', ['number' => $order_number]) }}</h2>
    <p>{{ __('notifications.portal_order.greeting', ['name' => $name]) }}</p>
    <p>{{ __('notifications.portal_order.message', ['number' => $order_number, 'customer' => $customer_name]) }}</p>

    <div style="background-color: #f0fdf4; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #bbf7d0;">
        <h3 style="margin-top: 0; color: #166534;">{{ __('notifications.portal_order.details') }}</h3>
        <p><strong>{{ __('notifications.portal_order.order_number') }}:</strong> {{ $order_number }}</p>
        <p><strong>{{ __('notifications.portal_order.customer') }}:</strong> {{ $customer_name }}</p>
        <p><strong>{{ __('notifications.portal_order.total') }}:</strong> {{ $total }}</p>
    </div>

    <div style="text-align: center;">
        <a href="{{ $action_url }}" class="btn">{{ __('notifications.portal_order.view_order') }}</a>
    </div>
@endsection
