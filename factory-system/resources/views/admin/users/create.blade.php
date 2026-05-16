<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>{{ __('admin.create_user') }}</title></head>
<body>
<h1>{{ __('admin.create_user') }}</h1>
<form method="POST" action="{{ route('admin.users.store') }}">
    @include('admin.users._form')
</form>
</body>
</html>
