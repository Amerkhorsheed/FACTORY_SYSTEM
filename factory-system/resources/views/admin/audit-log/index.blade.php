@extends('layouts.app')
@section('title', __('admin.audit_log'))
@section('page-title', __('admin.audit_log'))

@section('content')
<x-page-header :title="__('admin.audit_log')" />

<x-card>
    <x-filter-panel :action="route('admin.audit-log.index')" :reset="route('admin.audit-log.index')">
        <x-form-select name="log_name" :label="__('admin.log')">
            <option value="">{{ __('ui.labels.all') }}</option>
            @foreach($logNames as $logName)
                <option value="{{ $logName }}" @selected(request('log_name') === $logName)>{{ $logName }}</option>
            @endforeach
        </x-form-select>
        <x-form-select name="event" :label="__('admin.event')">
            <option value="">{{ __('ui.labels.all') }}</option>
            @foreach($events as $event)
                <option value="{{ $event }}" @selected(request('event') === $event)>{{ $event }}</option>
            @endforeach
        </x-form-select>
    </x-filter-panel>

    <div class="table-scroll"><table class="table">
        <thead>
            <tr>
                <th scope="col">{{ __('admin.id') }}</th>
                <th scope="col">{{ __('admin.log') }}</th>
                <th scope="col">{{ __('admin.event') }}</th>
                <th scope="col">{{ __('admin.description') }}</th>
                <th scope="col">{{ __('admin.date') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td><a class="action-link" href="{{ route('admin.audit-log.show', $log) }}" aria-label="{{ __('ui.actions.show') }} {{ $log->id }}">{{ $log->id }}</a></td>
                <td>{{ $log->log_name }}</td>
                <td>{{ $log->event }}</td>
                <td>{{ $log->description }}</td>
                <td>{{ $log->created_at }}</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$logs" />
</x-card>
@endsection
