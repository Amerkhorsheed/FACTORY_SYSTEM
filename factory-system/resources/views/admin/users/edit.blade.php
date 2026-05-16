<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>{{ __('admin.edit_user') }}</title></head>
<body>
<h1>{{ __('admin.edit_user') }}</h1>
<form method="POST" action="{{ route('admin.users.update', $user) }}">
    @method('PUT')
    @include('admin.users._form')
</form>
</body>
</html>
