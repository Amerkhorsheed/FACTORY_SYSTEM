<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; font-family: 'dejavu sans', sans-serif; }
        body { direction: rtl; color: #111827; font-size: 10pt; margin: 0; }
        .page { padding: 18mm 14mm; }
        .muted { color: #64748b; }
        .ltr { direction: ltr; text-align: left; }
        .header { border-bottom: 3px solid #1d4ed8; padding-bottom: 12px; margin-bottom: 18px; }
        .header-table, .meta, .items, .totals, .payments { width: 100%; border-collapse: collapse; }
        .brand { font-size: 18pt; font-weight: bold; color: #1e3a8a; }
        .doc-title { color: #1d4ed8; font-size: 22pt; font-weight: bold; text-align: left; }
        .badge { display: inline-block; border: 1px solid #bfdbfe; color: #1d4ed8; padding: 3px 8px; border-radius: 12px; font-size: 8pt; }
        .section-title { color: #1e3a8a; font-weight: bold; margin: 16px 0 6px; }
        .meta td { border: 1px solid #e2e8f0; padding: 7px 9px; vertical-align: top; }
        .label { color: #475569; font-size: 8pt; }
        .value { font-weight: bold; margin-top: 2px; }
        .items th, .payments th { background: #1e3a8a; color: white; padding: 8px 6px; border: 1px solid #1e3a8a; font-size: 9pt; }
        .items td, .payments td { padding: 7px 6px; border: 1px solid #e2e8f0; }
        .items tr:nth-child(even) td, .payments tr:nth-child(even) td { background: #f8fafc; }
        .totals { margin-top: 14px; width: 42%; margin-right: auto; }
        .totals td { padding: 7px 9px; border-bottom: 1px solid #e2e8f0; }
        .grand td { color: #1e3a8a; font-size: 12pt; font-weight: bold; border-top: 2px solid #1e3a8a; }
        .box { border: 1px solid #dbeafe; background: #eff6ff; padding: 10px 12px; margin-top: 12px; }
        .signatures { width: 100%; margin-top: 34px; border-collapse: collapse; }
        .signatures td { width: 50%; text-align: center; padding-top: 26px; border-top: 1px solid #94a3b8; color: #475569; }
        .footer { border-top: 1px solid #e2e8f0; margin-top: 22px; padding-top: 8px; text-align: center; font-size: 8pt; color: #64748b; }
    </style>
</head>
<body>
@php
    $money = fn ($amount) => number_format((int) $amount).' '.$settings['currency_label'];
    $status = config("factory.invoice_statuses.{$invoice->status}", $invoice->status);
    $items = $invoice->order?->items ?? collect();
@endphp
<div class="page">
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width:55%; vertical-align:top;">
                    <div class="brand">{{ $settings['factory_name'] }}</div>
                    <div class="muted">{{ $settings['factory_address'] }}</div>
                    <div class="muted">{{ $settings['factory_phone'] }}</div>
                    @if($settings['factory_tax_number'])<div class="muted">{{ $settings['factory_tax_number'] }}</div>@endif
                </td>
                <td style="width:45%; text-align:left; vertical-align:top;">
                    <div class="doc-title">{{ __('pdf.invoice.invoice') }}</div>
                    <div class="badge">{{ $status }}</div>
                    <div class="value ltr">{{ $invoice->invoice_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td style="width:33%;"><div class="label">{{ __('pdf.invoice.bill_to') }}</div><div class="value">{{ $invoice->customer?->name }}</div><div class="muted">{{ $invoice->customer?->phone }}</div><div class="muted">{{ $invoice->customer?->address }}</div></td>
            <td><div class="label">{{ __('pdf.invoice.issue_date') }}</div><div class="value">{{ $invoice->issue_date?->format('Y-m-d') }}</div></td>
            <td><div class="label">{{ __('pdf.invoice.due_date') }}</div><div class="value">{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">{{ __('pdf.invoice.order_number') }}</div><div class="value">{{ $invoice->order?->order_number ?? '—' }}</div></td>
            <td><div class="label">{{ __('pdf.common.currency') }}</div><div class="value">{{ $settings['currency_label'] }}</div></td>
            <td><div class="label">{{ __('pdf.common.generated_at') }}</div><div class="value">{{ $settings['generated_at'] }}</div></td>
        </tr>
    </table>

    <div class="section-title">{{ __('ui.modules.inventory') }}</div>
    <table class="items">
        <thead><tr><th style="width:5%">#</th><th>{{ __('pdf.invoice.item') }}</th><th style="width:12%">{{ __('pdf.invoice.quantity') }}</th><th style="width:18%">{{ __('pdf.invoice.unit_price') }}</th><th style="width:18%">{{ __('pdf.common.total') }}</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr><td class="ltr">{{ $loop->iteration }}</td><td>{{ $item->product?->name ?? '—' }}</td><td class="ltr">{{ number_format($item->quantity) }}</td><td class="ltr">{{ $money($item->unit_price) }}</td><td class="ltr">{{ $money($item->line_total) }}</td></tr>
        @empty
            <tr><td colspan="5" class="muted">{{ __('ui.messages.empty_title') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr><td>{{ __('pdf.invoice.subtotal') }}</td><td class="ltr">{{ $money($invoice->subtotal) }}</td></tr>
        <tr><td>{{ __('pdf.invoice.discount') }}</td><td class="ltr">{{ $money($invoice->discount_amount) }}</td></tr>
        <tr><td>{{ __('pdf.invoice.tax') }}</td><td class="ltr">{{ $money($invoice->tax_amount) }}</td></tr>
        <tr class="grand"><td>{{ __('pdf.common.total') }}</td><td class="ltr">{{ $money($invoice->total_amount) }}</td></tr>
        <tr><td>{{ __('pdf.invoice.paid') }}</td><td class="ltr">{{ $money($invoice->paid_amount) }}</td></tr>
        <tr><td>{{ __('pdf.invoice.balance_due') }}</td><td class="ltr">{{ $money($invoice->balance_due) }}</td></tr>
    </table>

    <div class="box"><strong>{{ __('pdf.common.amount_words') }}:</strong> {{ $amountWords }}</div>

    @if($invoice->payments->isNotEmpty())
        <div class="section-title">{{ __('pdf.invoice.payments') }}</div>
        <table class="payments"><thead><tr><th>{{ __('pdf.common.date') }}</th><th>{{ __('pdf.invoice.reference') }}</th><th>{{ __('pdf.invoice.paid') }}</th></tr></thead><tbody>
        @foreach($invoice->payments as $payment)
            <tr><td>{{ $payment->payment_date?->format('Y-m-d') }}</td><td>{{ $payment->payment_number ?? $payment->reference_number }}</td><td class="ltr">{{ $money($payment->amount) }}</td></tr>
        @endforeach
        </tbody></table>
    @endif

    @if($invoice->notes)<div class="box"><strong>{{ __('pdf.common.notes') }}:</strong> {{ $invoice->notes }}</div>@endif
    @if($settings['invoice_terms'])<div class="box"><strong>{{ __('pdf.invoice.terms') }}:</strong> {{ $settings['invoice_terms'] }}</div>@endif

    <table class="signatures"><tr><td>{{ __('pdf.common.authorized_signature') }}</td><td>{{ __('pdf.common.customer_signature') }}</td></tr></table>
    <div class="footer">{{ $settings['invoice_footer_text'] ?: __('pdf.common.page_footer') }}</div>
</div>
</body>
</html>
