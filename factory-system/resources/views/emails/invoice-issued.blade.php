@extends('emails.layout')

@section('content')
    <h2>{{ __('notifications.invoice_issued.subject', ['number' => $invoice_number]) }}</h2>
    <p>{{ __('notifications.invoice_issued.body', ['number' => $invoice_number]) }}</p>
    
    <div style="background-color: #eff6ff; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #bfdbfe;">
        <table style="width: 100%;">
            <tr>
                <td style="color: #4b5563;">{{ __('notifications.invoice_issued.total') }}:</td>
                <td style="font-weight: bold; text-align: left;">{{ $total }}</td>
            </tr>
        </table>
    </div>
    
    <div style="text-align: center;">
        <a href="{{ $action_url }}" class="btn">{{ __('notifications.actions.open_invoice') }}</a>
    </div>
@endsection
