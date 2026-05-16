<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'dejavu sans', sans-serif; }
        body { direction: rtl; font-size: 10pt; color: #111827; margin: 20px; }
        .header { border-bottom: 3px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; }
        .company-name { font-size: 18pt; font-weight: bold; color: #1e3a8a; }
        .title { font-size: 18pt; font-weight: bold; color: #1e3a8a; text-align: center; margin: 15px 0; }
        .meta td { padding: 4px 8px; }
        .meta-label { font-weight: bold; color: #374151; }
        .txn { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .txn th { background: #1e3a8a; color: #fff; padding: 8px; text-align: right; font-size: 9pt; }
        .txn td { padding: 6px 8px; border-bottom: 1px solid #d1d5db; text-align: right; font-size: 9pt; }
        .txn tr:nth-child(even) td { background: #f3f4f6; }
        .balance-box { background: #eff6ff; padding: 15px; border-radius: 6px; margin-top: 20px; overflow: hidden; }
        .bal-item { float: right; width: 33%; text-align: center; }
        .bal-value { font-size: 14pt; font-weight: bold; color: #1e3a8a; }
        .footer { margin-top: 40px; text-align: center; color: #6b7280; font-size: 8pt; border-top: 1px solid #d1d5db; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('factory.name') }}</div>
        <div style="color: #6b7280;">كشف حساب عميل</div>
    </div>

    <div class="title">كشف حساب — {{ $customer->name }}</div>

    <table class="meta">
        <tr><td class="meta-label">رمز العميل:</td><td>{{ $customer->customer_code ?? '—' }}</td><td class="meta-label">الفئة:</td><td>{{ $customer->category ?? '—' }}</td></tr>
        <tr><td class="meta-label">الفترة:</td><td>{{ $dateFrom ?? '—' }} إلى {{ $dateTo ?? '—' }}</td><td class="meta-label">الهاتف:</td><td>{{ $customer->phone ?? '—' }}</td></tr>
    </table>

    <table class="txn">
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>النوع</th>
                <th>الرقم</th>
                <th>مدين</th>
                <th>دائن</th>
                <th>الرصيد</th>
            </tr>
        </thead>
        <tbody>
            @php $runningBalance = $openingBalance ?? 0; @endphp
            @foreach ($transactions ?? [] as $txn)
            @php
                $runningBalance += ($txn['debit'] ?? 0) - ($txn['credit'] ?? 0);
            @endphp
            <tr>
                <td>{{ $txn['date'] ?? '—' }}</td>
                <td>{{ $txn['type'] ?? '—' }}</td>
                <td>{{ $txn['reference'] ?? '—' }}</td>
                <td>{{ ($txn['debit'] ?? 0) > 0 ? number_format($txn['debit']) : '—' }}</td>
                <td>{{ ($txn['credit'] ?? 0) > 0 ? number_format($txn['credit']) : '—' }}</td>
                <td>{{ number_format($runningBalance) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="balance-box">
        <div class="bal-item">
            <div style="color: #6b7280;">الرصيد الافتتاحي</div>
            <div class="bal-value">{{ number_format($openingBalance ?? 0) }} ل.س</div>
        </div>
        <div class="bal-item">
            <div style="color: #6b7280;">إجمالي الحركات</div>
            <div class="bal-value">{{ number_format(count($transactions ?? [])) }}</div>
        </div>
        <div class="bal-item">
            <div style="color: #6b7280;">الرصيد الختامي</div>
            <div class="bal-value">{{ number_format($runningBalance ?? 0) }} ل.س</div>
        </div>
    </div>

    <div class="footer">
        {{ config('factory.name') }} · كشف حساب · {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
