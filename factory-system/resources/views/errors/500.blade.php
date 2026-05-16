<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('ui.error_500') }} — {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', 'Segoe UI', sans-serif;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            direction: rtl;
            color: #111827;
        }
        .container { text-align: center; max-width: 480px; padding: 2rem; }
        .code { font-size: 6rem; font-weight: 800; color: #bfdbfe; line-height: 1; }
        h1 { font-size: 1.5rem; margin: 0.5rem 0; }
        p { color: #6b7280; margin-bottom: 2rem; }
        a {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #2563eb;
            color: #fff;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: background 0.2s;
        }
        a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">500</div>
        <h1>خطأ في الخادم</h1>
        <p>حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى لاحقاً أو التواصل مع الدعم الفني.</p>
        <a href="/">العودة للرئيسية</a>
    </div>
</body>
</html>
