<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'dejavu sans', sans-serif; }
        body { direction: rtl; font-size: 10pt; color: #111827; margin: 20px; }
        .header { border-bottom: 3px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; overflow: hidden; }
        .company { float: right; }
        .company-name { font-size: 18pt; font-weight: bold; color: #1e3a8a; }
        .invoice-num { float: left; text-align: left; }
        .invoice-title { font-size: 22pt; font-weight: bold; color: #1e3a8a; text-align: center; margin: 10px 0; clear: both; }
        .meta { width: 100%; margin-bottom: 20px; }
        .meta td { padding: 4px 8px; vertical-align: top; }
        .meta-label { font-weight: bold; color: #374151; width: 120px; }
        .items { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items th { background: #1e3a8a; color: #fff; padding: 8px; text-align: right; }
        .items td { padding: 8px; border-bottom: 1px solid #d1d5db; text-align: right; }
        .items tr:nth-child(even) td { background: #f3f4f6; }
        .totals { width: 300px; margin-right: auto; margin-top: 20px; }
        .totals td { padding: 6px 10px; }
        .total-row { font-weight: bold; font-size: 12pt; border-top: 2px solid #1e3a8a; color: #1e3a8a; }
        .footer { margin-top: 40px; text-align: center; color: #6b7280; font-size: 8pt; border-top: 1px solid #d1d5db; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">
            <div class="company-name">{{ config('factory.name') }}</div>
            <div style="color: #6b7280;">{{ $settings['address'] ?? '' }}</div>
        </div>
        <div class="invoice-num">
            <div style="font-size: 9pt; color: #6b7280;">رقم الفاتورة</div>
            <div style="font-size: 14pt; font-weight: bold;">{{ $invoice->invoice_number }}</div>
        </div>
    </div>

    <div class="invoice-title">فاتورة</div>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <table>
                    <tr><td class="meta-label">العميل:</td><td>{{ $invoice->customer->name ?? '—' }}</td></tr>
                    <tr><td class="meta-label">الهاتف:</td><td>{{ $invoice->customer->phone ?? '—' }}</td></tr>
                    <tr><td class="meta-label">العنوان:</td><td>{{ $invoice->customer->address ?? '—' }}</td></tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table>
                    <tr><td class="meta-label">تاريخ الإصدار:</td><td>{{ $invoice->issue_date?->format('Y-m-d') }}</td></tr>
                    <tr><td class="meta-label">تاريخ الاستحقاق:</td><td>{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</td></tr>
                    <tr><td class="meta-label">رقم الطلب:</td><td>{{ $invoice->order?->order_number ?? '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>المنتج</th>
                <th>الكمية</th>
                <th>سعر الوحدة</th>
                <th>المجموع</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->order?->items ?? [] as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product?->name ?? '—' }}</td>
                <td>{{ number_format($item->quantity) }}</td>
                <td>{{ number_format($item->unit_price) }} ل.س</td>
                <td>{{ number_format($item->line_total) }} ل.س</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="meta-label">المجموع الفرعي:</td><td>{{ number_format($invoice->subtotal ?? $invoice->total_amount) }} ل.س</td></tr>
        @if (($invoice->tax_amount ?? 0) > 0)
        <tr><td class="meta-label">الضريبة:</td><td>{{ number_format($invoice->tax_amount) }} ل.س</td></tr>
        @endif
        <tr class="total-row"><td>الإجمالي:</td><td>{{ number_format($invoice->total_amount) }} ل.س</td></tr>
        <tr><td class="meta-label">المدفوع:</td><td>{{ number_format($invoice->paid_amount ?? 0) }} ل.س</td></tr>
        <tr style="font-weight: bold;"><td>المتبقي:</td><td>{{ number_format($invoice->balance_due ?? $invoice->total_amount) }} ل.س</td></tr>
    </table>

    <div class="footer">
        {{ config('factory.name') }} · تم إنشاء هذه الفاتورة آلياً · {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
