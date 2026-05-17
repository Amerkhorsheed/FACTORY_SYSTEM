<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; font-family: 'dejavu sans', sans-serif; }
        body { direction: rtl; color: #111827; font-size: 10pt; margin: 0; }
        .page { padding: 16mm 12mm; }
        .muted { color: #64748b; }
        .ltr { direction: ltr; text-align: left; }
        .header { border-bottom: 3px solid #1e3a8a; margin-bottom: 15px; padding-bottom: 10px; }
        .brand { color: #1e3a8a; font-size: 18pt; font-weight: bold; }
        .title { color: #1e3a8a; font-size: 19pt; font-weight: bold; text-align: left; }
        .meta, .orders, .summary, .signatures { width: 100%; border-collapse: collapse; }
        .meta td { border: 1px solid #e2e8f0; padding: 8px; vertical-align: top; }
        .label { color: #475569; font-size: 8pt; }
        .value { font-weight: bold; margin-top: 2px; }
        .orders th { background: #1e3a8a; color: white; padding: 7px 5px; border: 1px solid #1e3a8a; font-size: 8.5pt; }
        .orders td { padding: 7px 5px; border: 1px solid #e2e8f0; vertical-align: top; }
        .orders tr:nth-child(even) td { background: #f8fafc; }
        .summary td { width: 33.33%; background: #eff6ff; border: 1px solid #bfdbfe; padding: 9px; text-align: center; }
        .summary .num { color: #1e3a8a; font-size: 14pt; font-weight: bold; }
        .signatures { margin-top: 34px; }
        .signatures td { width: 33.33%; text-align: center; padding-top: 26px; border-top: 1px solid #94a3b8; color: #475569; }
        .footer { border-top: 1px solid #e2e8f0; margin-top: 18px; padding-top: 8px; text-align: center; font-size: 8pt; color: #64748b; }
    </style>
</head>
<body>
@php
    $money = fn ($amount) => number_format((int) $amount).' '.$settings['currency_label'];
    $status = config("factory.shipment_statuses.{$shipment->status}", $shipment->status);
    $ordersTotal = (int) $shipment->orders->sum('total_amount');
    $itemsTotal = (int) $shipment->orders->sum(fn ($order) => $order->items->sum('quantity'));
@endphp
<div class="page">
    <div class="header">
        <table style="width:100%; border-collapse:collapse;"><tr>
            <td><div class="brand">{{ $settings['factory_name'] }}</div><div class="muted">{{ $settings['factory_address'] }}</div><div class="muted">{{ $settings['factory_phone'] }}</div></td>
            <td style="text-align:left;"><div class="title">{{ __('pdf.manifest.manifest') }}</div><div class="value ltr">{{ $shipment->shipment_number }}</div></td>
        </tr></table>
    </div>

    <table class="meta">
        <tr>
            <td><div class="label">{{ __('pdf.manifest.shipment_number') }}</div><div class="value">{{ $shipment->shipment_number }}</div></td>
            <td><div class="label">{{ __('pdf.manifest.shipment_date') }}</div><div class="value">{{ $shipment->shipment_date?->format('Y-m-d') }}</div></td>
            <td><div class="label">{{ __('pdf.invoice.status') }}</div><div class="value">{{ $status }}</div></td>
        </tr>
        <tr>
            <td><div class="label">{{ __('pdf.manifest.plate_number') }}</div><div class="value">{{ $shipment->truck?->plate_number ?? '—' }}</div></td>
            <td><div class="label">{{ __('pdf.manifest.driver') }}</div><div class="value">{{ $shipment->driver?->name ?? '—' }}</div></td>
            <td><div class="label">{{ __('pdf.manifest.driver_phone') }}</div><div class="value ltr">{{ $shipment->driver?->phone ?? '—' }}</div></td>
        </tr>
    </table>

    <table class="summary" style="margin:12px 0;"><tr>
        <td><div class="label">{{ __('pdf.manifest.orders_count') }}</div><div class="num">{{ $shipment->orders->count() }}</div></td>
        <td><div class="label">{{ __('pdf.manifest.products_count') }}</div><div class="num">{{ number_format($itemsTotal) }}</div></td>
        <td><div class="label">{{ __('pdf.common.total') }}</div><div class="num">{{ $money($ordersTotal) }}</div></td>
    </tr></table>

    <table class="orders">
        <thead><tr><th style="width:4%">#</th><th style="width:16%">{{ __('pdf.manifest.order_number') }}</th><th style="width:19%">{{ __('ui.fields.customer') }}</th><th>{{ __('pdf.manifest.address') }}</th><th style="width:13%">{{ __('pdf.manifest.phone') }}</th><th style="width:14%">{{ __('pdf.common.total') }}</th><th style="width:14%">{{ __('pdf.common.customer_signature') }}</th></tr></thead>
        <tbody>
        @forelse($shipment->orders as $order)
            <tr><td class="ltr">{{ $loop->iteration }}</td><td>{{ $order->order_number }}</td><td>{{ $order->customer?->name ?? '—' }}</td><td>{{ $order->customer?->address ?? '—' }}</td><td class="ltr">{{ $order->customer?->phone ?? '—' }}</td><td class="ltr">{{ $money($order->total_amount) }}</td><td></td></tr>
        @empty
            <tr><td colspan="7" class="muted">{{ __('ui.messages.empty_title') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    @if($shipment->notes)<div style="margin-top:12px;"><strong>{{ __('pdf.common.notes') }}:</strong> {{ $shipment->notes }}</div>@endif
    <table class="signatures"><tr><td>{{ __('pdf.manifest.driver') }}</td><td>{{ __('pdf.manifest.supervisor_signature') }}</td><td>{{ __('pdf.common.authorized_signature') }}</td></tr></table>
    <div class="footer">{{ __('pdf.common.page_footer') }} · {{ __('pdf.common.generated_at') }}: {{ $settings['generated_at'] }}</div>
</div>
</body>
</html>
