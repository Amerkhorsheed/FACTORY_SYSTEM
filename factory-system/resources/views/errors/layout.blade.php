<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fafc; color: #0f172a; direction: rtl; font-family: Tahoma, Arial, sans-serif; }
        main { width: min(92vw, 520px); padding: 40px 28px; text-align: center; background: white; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08); }
        .code { font-size: clamp(72px, 18vw, 120px); line-height: 1; font-weight: 900; color: #dbeafe; }
        h1 { margin: 16px 0 10px; font-size: 24px; }
        p { margin: 0 auto 28px; max-width: 420px; color: #64748b; line-height: 1.9; }
        a { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 22px; border-radius: 999px; background: #2563eb; color: white; font-weight: 700; text-decoration: none; }
        a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <main>
        <div class="code">@yield('code')</div>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        <a href="@yield('href', '/')">@yield('action')</a>
    </main>
</body>
</html>
