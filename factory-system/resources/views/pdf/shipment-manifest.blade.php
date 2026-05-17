<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'dejavu sans', sans-serif; }
        body { direction: rtl; font-size: 10pt; color: #111827; margin: 20px; }
        .header { border-bottom: 3px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; overflow: hidden; }
        .company-name { font-size: 18pt; font-weight: bold; color: #1e3a8a; }
        .title { font-size: 20pt; font-weight: bold; color: #1e3a8a; text-align: center; margin: 15px 0; }
        .meta td { padding: 4px 8px; }
        .meta-label { font-weight: bold; color: #374151; }
        .orders { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .orders th { background: #1e3a8a; color: #fff; padding: 8px; text-align: right; }
        .orders td { padding: 8px; border-bottom: 1px solid #d1d5db; text-align: right; }
        .orders tr:nth-child(even) td { background: #f3f4f6; }
        .summary { background: #eff6ff; padding: 15px; border-radius: 6px; margin-top: 20px; }
        .footer { margin-top: 40px; text-align: center; color: #6b7280; font-size: 8pt; border-top: 1px solid #d1d5db; padding-top: 10px; }
        .signature { margin-top: 50px; overflow: hidden; }
        .sig-block { width: 45%; float: right; text-align: center; }
        .sig-block:last-child { float: left; }
        .sig-line { border-top: 1px solid #374151; margin-top: 50px; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('factory.name') }}</div>
        <div style="color: #6b7280;">بيان شحنة</div>
    </div>

    <div class="title">بيان الشحنة {{ $shipment->shipment_number ?? '' }}</div>

    <table class="meta">
        <tr><td class="meta-label">رقم الشحنة:</td><td>{{ $shipment->shipment_number }}</td><td class="meta-label">تاريخ الإرسال:</td><td>{{ $shipment->shipment_date?->format('Y-m-d') ?? '—' }}</td></tr>
        <tr><td class="meta-label">الشاحنة:</td><td>{{ $shipment->truck?->plate_number ?? '—' }}</td><td class="meta-label">السائق:</td><td>{{ $shipment->driver?->name ?? '—' }}</td></tr>
        <tr><td class="meta-label">الحالة:</td><td>{{ config("factory.shipment_statuses.{$shipment->status}", $shipment->status) }}</td><td class="meta-label">ملاحظات:</td><td>{{ $shipment->notes ?? '—' }}</td></tr>
    </table>

    <table class="orders">
        <thead>
            <tr>
                <th>#</th>
                <th>رقم الطلب</th>
                <th>العميل</th>
                <th>المنطقة</th>
                <th>المبلغ</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($shipment->orders ?? [] as $i => $order)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->customer?->name ?? '—' }}</td>
                <td>{{ $order->customer?->region ?? '—' }}</td>
                <td>{{ number_format($order->total_amount) }} ل.س</td>
                <td>{{ config("factory.order_statuses.{$order->status}", $order->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <strong>إجمالي الطلبات:</strong> {{ count($shipment->orders ?? []) }} ·
        <strong>إجمالي المبلغ:</strong> {{ number_format(collect($shipment->orders ?? [])->sum('total_amount')) }} ل.س
    </div>

    <div class="signature">
        <div class="sig-block">
            <div class="sig-line">توقيع المسؤول</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">توقيع السائق</div>
        </div>
    </div>

    <div class="footer">
        {{ config('factory.name') }} · بيان شحنة · {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
