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
        .meta, .summary, .transactions { width: 100%; border-collapse: collapse; }
        .meta td { border: 1px solid #e2e8f0; padding: 8px; vertical-align: top; }
        .label { color: #475569; font-size: 8pt; }
        .value { font-weight: bold; margin-top: 2px; }
        .summary td { width: 25%; background: #eff6ff; border: 1px solid #bfdbfe; padding: 8px; text-align: center; }
        .summary .num { color: #1e3a8a; font-weight: bold; font-size: 12pt; }
        .transactions th { background: #1e3a8a; color: white; padding: 7px 5px; border: 1px solid #1e3a8a; font-size: 8.5pt; }
        .transactions td { padding: 7px 5px; border: 1px solid #e2e8f0; }
        .transactions tr:nth-child(even) td { background: #f8fafc; }
        .opening td, .closing td { background: #f1f5f9; font-weight: bold; }
        .debit { color: #dc2626; }
        .credit { color: #15803d; }
        .footer { border-top: 1px solid #e2e8f0; margin-top: 18px; padding-top: 8px; text-align: center; font-size: 8pt; color: #64748b; }
    </style>
</head>
<body>
@php($money = fn ($amount) => number_format((int) $amount).' '.$settings['currency_label'])
<div class="page">
    <div class="header">
        <table style="width:100%; border-collapse:collapse;"><tr>
            <td><div class="brand">{{ $settings['factory_name'] }}</div><div class="muted">{{ $settings['factory_address'] }}</div><div class="muted">{{ $settings['factory_phone'] }}</div></td>
            <td style="text-align:left;"><div class="title">{{ __('pdf.statement.customer_statement') }}</div><div class="muted">{{ __('pdf.common.generated_at') }}: {{ $settings['generated_at'] }}</div></td>
        </tr></table>
    </div>

    <table class="meta">
        <tr>
            <td><div class="label">{{ __('ui.fields.customer') }}</div><div class="value">{{ $customer->name }}</div><div class="muted">{{ $customer->code }}</div></td>
            <td><div class="label">{{ __('ui.fields.phone') }}</div><div class="value ltr">{{ $customer->phone }}</div></td>
            <td><div class="label">{{ __('pdf.statement.period') }}</div><div class="value">{{ $from->format('Y-m-d') }} - {{ $to->format('Y-m-d') }}</div></td>
        </tr>
    </table>

    <table class="summary" style="margin:12px 0;"><tr>
        <td><div class="label">{{ __('pdf.statement.opening_balance') }}</div><div class="num">{{ $money($statement['opening_balance']) }}</div></td>
        <td><div class="label">{{ __('pdf.statement.total_debit') }}</div><div class="num debit">{{ $money($statement['total_debit']) }}</div></td>
        <td><div class="label">{{ __('pdf.statement.total_credit') }}</div><div class="num credit">{{ $money($statement['total_credit']) }}</div></td>
        <td><div class="label">{{ __('pdf.statement.closing_balance') }}</div><div class="num">{{ $money($statement['closing_balance']) }}</div></td>
    </tr></table>

    <table class="transactions">
        <thead><tr><th style="width:13%">{{ __('pdf.common.date') }}</th><th style="width:17%">{{ __('pdf.statement.reference') }}</th><th>{{ __('pdf.statement.description') }}</th><th style="width:15%">{{ __('pdf.statement.debit') }}</th><th style="width:15%">{{ __('pdf.statement.credit') }}</th><th style="width:15%">{{ __('pdf.statement.running_balance') }}</th></tr></thead>
        <tbody>
            <tr class="opening"><td></td><td colspan="4">{{ __('pdf.statement.opening_balance') }}</td><td class="ltr">{{ $money($statement['opening_balance']) }}</td></tr>
            @forelse($statement['transactions'] as $row)
                <tr><td>{{ $row['date']?->format('Y-m-d') }}</td><td>{{ $row['reference'] ?? '—' }}</td><td>{{ $row['description'] }}</td><td class="ltr debit">{{ $row['debit'] > 0 ? $money($row['debit']) : '—' }}</td><td class="ltr credit">{{ $row['credit'] > 0 ? $money($row['credit']) : '—' }}</td><td class="ltr">{{ $money($row['balance']) }}</td></tr>
            @empty
                <tr><td colspan="6" class="muted">{{ __('ui.messages.empty_title') }}</td></tr>
            @endforelse
            <tr class="closing"><td></td><td colspan="4">{{ __('pdf.statement.closing_balance') }}</td><td class="ltr">{{ $money($statement['closing_balance']) }}</td></tr>
        </tbody>
    </table>

    <div class="footer">{{ __('pdf.common.page_footer') }}</div>
</div>
</body>
</html>
