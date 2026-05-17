@extends('emails.layout')

@section('content')
    <h2>{{ __('notifications.order_status.subject', ['number' => $order_number, 'status' => $status]) }}</h2>
    <p>{{ __('notifications.order_status.body', ['number' => $order_number]) }}</p>
    
    <div style="background-color: #eff6ff; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #bfdbfe;">
        <h3 style="margin-top: 0; color: #1e3a8a;">{{ __('notifications.order_status.status') }}: {{ $status }}</h3>
    </div>
    
    <div style="text-align: center;">
        <a href="{{ $action_url }}" class="btn">{{ __('notifications.actions.open_order') }}</a>
    </div>
@endsection
