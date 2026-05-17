@extends('emails.layout')

@section('content')
    <h2 style="color: #dc2626;">{{ __('notifications.invoice_overdue.subject', ['count' => $invoices->count()]) }}</h2>
    <p>{{ __('notifications.invoice_overdue.body') }}</p>
    
    <div style="background-color: #fef2f2; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #fecaca;">
        <table style="width: 100%;">
            <tr>
                <td style="color: #4b5563; padding: 5px 0;">{{ __('notifications.invoice_overdue.count') }}:</td>
                <td style="font-weight: bold; text-align: left; color: #991b1b;">{{ $invoices->count() }}</td>
            </tr>
            <tr>
                <td style="color: #4b5563; padding: 5px 0;">{{ __('notifications.invoice_overdue.total_due') }}:</td>
                <td style="font-weight: bold; text-align: left; color: #991b1b;">{{ $total_due }}</td>
            </tr>
        </table>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <thead>
            <tr>
                <th style="text-align: right; border-bottom: 1px solid #e5e7eb; padding: 8px;">{{ __('notifications.invoice_overdue.number') }}</th>
                <th style="text-align: right; border-bottom: 1px solid #e5e7eb; padding: 8px;">{{ __('notifications.invoice_overdue.customer') }}</th>
                <th style="text-align: left; border-bottom: 1px solid #e5e7eb; padding: 8px;">{{ __('notifications.invoice_overdue.total_due') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices->take(8) as $invoice)
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;">{{ $invoice->invoice_number }}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;">{{ $invoice->customer?->name }}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #f3f4f6; text-align: left;">{{ money_format($invoice->balance_due) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="text-align: center;">
        <a href="{{ $action_url }}" class="btn" style="background-color: #dc2626;">{{ __('notifications.actions.open_report') }}</a>
    </div>
@endsection
