@extends('emails.layout')

@section('content')
    <h2>{{ __('notifications.payment_received.subject', ['invoice' => $invoice_number]) }}</h2>
    <p>{{ __('notifications.payment_received.body') }}</p>
    
    <div style="background-color: #f0fdf4; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #bbf7d0;">
        <table style="width: 100%;">
            <tr>
                <td style="color: #4b5563;">{{ __('notifications.payment_received.amount') }}:</td>
                <td style="font-weight: bold; text-align: left; color: #166534;">{{ $amount }}</td>
            </tr>
        </table>
    </div>
    
    <div style="text-align: center;">
        <a href="{{ $action_url }}" class="btn">{{ __('notifications.actions.open_invoice') }}</a>
    </div>
@endsection
