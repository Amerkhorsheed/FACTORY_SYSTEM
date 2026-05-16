@extends('layouts.app')
@section('title', __('admin.audit_log'))
@section('page-title', __('admin.audit_log'))

@section('content')
<x-page-header :title="__('admin.audit_log')" />

<x-card>
    <form method="GET" action="{{ route('admin.audit-log.index') }}" class="mb-5 grid gap-3 md:grid-cols-4">
        <x-form-select name="log_name" label="Log">
            <option value="">{{ __('ui.actions.search') }}</option>
            @foreach($logNames as $logName)
                <option value="{{ $logName }}" @selected(request('log_name') === $logName)>{{ $logName }}</option>
            @endforeach
        </x-form-select>
        <x-form-select name="event" label="Event">
            <option value="">{{ __('ui.actions.search') }}</option>
            @foreach($events as $event)
                <option value="{{ $event }}" @selected(request('event') === $event)>{{ $event }}</option>
            @endforeach
        </x-form-select>
        <div class="flex items-end"><x-btn type="submit">{{ __('ui.actions.search') }}</x-btn></div>
    </form>

    <div class="table-scroll"><table class="table">
        <thead><tr><th>ID</th><th>Log</th><th>Event</th><th>Description</th><th>Date</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td><a class="font-bold text-brand-700" href="{{ route('admin.audit-log.show', $log) }}">{{ $log->id }}</a></td>
                <td>{{ $log->log_name }}</td><td>{{ $log->event }}</td><td>{{ $log->description }}</td><td>{{ $log->created_at }}</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$logs" />
</x-card>
@endsection
