<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'dejavu sans', sans-serif; }
        body { direction: rtl; font-size: 10pt; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1e3a8a; color: #fff; padding: 8px 6px; text-align: right; font-size: 9pt; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; text-align: right; font-size: 9pt; }
        tr:nth-child(even) td { background: #f9fafb; }
        h2 { color: #1e3a8a; margin-bottom: 4px; }
    </style>
</head>
<body>
    <h2>{{ $title ?? 'تقرير' }}</h2>
    <table>
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach (array_values((array) $row) as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
