<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>{{ __('admin.audit_log') }}</title></head>
<body>
<h1>{{ __('admin.audit_log') }} #{{ $activity->id }}</h1>
<p>{{ $activity->log_name }}</p>
<p>{{ $activity->event }}</p>
<p>{{ $activity->description }}</p>
<pre dir="ltr">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</body>
</html>
