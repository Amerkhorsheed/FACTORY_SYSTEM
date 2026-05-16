@extends('layouts.app')
@section('title', __('admin.audit_log'))
@section('page-title', __('admin.audit_log'))

@section('content')
<x-page-header :title="__('admin.audit_log').' #'.$activity->id" :back="route('admin.audit-log.index')" />
<x-card>
    <dl class="grid gap-4 sm:grid-cols-2">
        <div><dt class="text-xs text-slate-500">Log</dt><dd class="font-bold">{{ $activity->log_name }}</dd></div>
        <div><dt class="text-xs text-slate-500">Event</dt><dd class="font-bold">{{ $activity->event }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Description</dt><dd class="font-bold">{{ $activity->description }}</dd></div>
    </dl>
    <pre class="mt-5 overflow-auto rounded-xl bg-slate-950 p-4 text-left text-xs text-slate-100" dir="ltr">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</x-card>
@endsection
