<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Tahoma', 'Arial', sans-serif; background-color: #f3f4f6; color: #1f2937; line-height: 1.6; margin: 0; padding: 0; direction: rtl; text-align: right; }
        .wrapper { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-top: 4px solid #1e3a8a; border-radius: 8px; margin-top: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { text-align: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #1e3a8a; text-decoration: none; }
        .content { margin-bottom: 30px; }
        .btn { display: inline-block; background-color: #1e3a8a; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 15px; font-weight: bold; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <a href="{{ config('app.url') }}" class="logo">{{ config('factory.name', 'المعمل') }}</a>
        </div>
        
        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('factory.name', 'المعمل') }}. {{ __('notifications.email.copyright') }}</p>
            <p>{{ __('notifications.email.auto') }}</p>
        </div>
    </div>
</body>
</html>
