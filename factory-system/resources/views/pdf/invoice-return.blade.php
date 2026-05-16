<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'dejavu sans', sans-serif; }
        body { direction: rtl; font-size: 10pt; color: #111827; margin: 20px; }
        .header { border-bottom: 3px solid #dc2626; padding-bottom: 15px; margin-bottom: 20px; }
        .company-name { font-size: 18pt; font-weight: bold; color: #1e3a8a; }
        .title { font-size: 20pt; font-weight: bold; color: #dc2626; text-align: center; margin: 15px 0; }
        .watermark { color: #fecaca; font-size: 8pt; text-align: center; margin-bottom: 10px; }
        .meta td { padding: 4px 8px; }
        .meta-label { font-weight: bold; color: #374151; }
        .items { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items th { background: #dc2626; color: #fff; padding: 8px; text-align: right; }
        .items td { padding: 8px; border-bottom: 1px solid #d1d5db; text-align: right; }
        .totals { width: 300px; margin-right: auto; margin-top: 20px; }
        .totals td { padding: 6px 10px; }
        .total-row { font-weight: bold; font-size: 12pt; border-top: 2px solid #dc2626; color: #dc2626; }
        .footer { margin-top: 40px; text-align: center; color: #6b7280; font-size: 8pt; border-top: 1px solid #d1d5db; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('factory.name') }}</div>
        <div style="color: #6b7280;">إشعار مرتجع</div>
    </div>

    <div class="title">إشعار مرتجع</div>
    <div class="watermark">مرتجع — RETURN</div>

    <table class="meta">
        <tr><td class="meta-label">رقم الفاتورة الأصلية:</td><td>{{ $invoice->invoice_number ?? '—' }}</td></tr>
        <tr><td class="meta-label">العميل:</td><td>{{ $invoice->customer?->name ?? '—' }}</td></tr>
        <tr><td class="meta-label">تاريخ المرتجع:</td><td>{{ now()->format('Y-m-d') }}</td></tr>
        <tr><td class="meta-label">سبب المرتجع:</td><td>{{ $reason ?? '—' }}</td></tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>المنتج</th>
                <th>الكمية المرتجعة</th>
                <th>سعر الوحدة</th>
                <th>المجموع</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($returnItems ?? [] as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item['product_name'] ?? '—' }}</td>
                <td>{{ number_format($item['quantity'] ?? 0) }}</td>
                <td>{{ number_format($item['unit_price'] ?? 0) }} ل.س</td>
                <td>{{ number_format($item['total'] ?? 0) }} ل.س</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr class="total-row"><td>إجمالي المرتجع:</td><td>{{ number_format($returnTotal ?? 0) }} ل.س</td></tr>
    </table>

    <div class="footer">
        {{ config('factory.name') }} · إشعار مرتجع · {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
