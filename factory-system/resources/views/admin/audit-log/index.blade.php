<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>{{ __('admin.audit_log') }}</title></head>
<body>
<h1>{{ __('admin.audit_log') }}</h1>
<form method="GET" action="{{ route('admin.audit-log.index') }}">
    <select name="log_name">
        <option value="">log</option>
        @foreach($logNames as $logName)
            <option value="{{ $logName }}" @selected(request('log_name') === $logName)>{{ $logName }}</option>
        @endforeach
    </select>
    <select name="event">
        <option value="">event</option>
        @foreach($events as $event)
            <option value="{{ $event }}" @selected(request('event') === $event)>{{ $event }}</option>
        @endforeach
    </select>
    <button type="submit">{{ __('admin.save') }}</button>
</form>
<table>
    <thead><tr><th>ID</th><th>Log</th><th>Event</th><th>Description</th><th>Date</th></tr></thead>
    <tbody>
    @forelse($logs as $log)
        <tr>
            <td><a href="{{ route('admin.audit-log.show', $log) }}">{{ $log->id }}</a></td>
            <td>{{ $log->log_name }}</td>
            <td>{{ $log->event }}</td>
            <td>{{ $log->description }}</td>
            <td>{{ $log->created_at }}</td>
        </tr>
    @empty
        <tr><td colspan="5">{{ __('admin.audit_log') }}</td></tr>
    @endforelse
    </tbody>
</table>
{{ $logs->links() }}
</body>
</html>
