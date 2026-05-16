@extends('layouts.app')
@section('title', __('admin.users'))
@section('page-title', __('admin.users'))

@section('content')
<x-page-header :title="__('admin.users')">
    <x-btn :href="route('admin.users.create')">{{ __('admin.create_user') }}</x-btn>
</x-page-header>

<x-card>
    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-5 grid gap-3 md:grid-cols-4">
        <x-form-input name="search" :value="request('search')" :label="__('ui.actions.search')" />
        <x-form-select name="role" :label="__('admin.role')">
            <option value="">{{ __('ui.actions.search') }}</option>
            @foreach($roles as $role)
                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>
            @endforeach
        </x-form-select>
        <div class="md:col-span-2 flex items-end"><x-btn type="submit">{{ __('ui.actions.search') }}</x-btn></div>
    </form>

    <div class="table-scroll"><table class="table">
        <thead><tr><th>{{ __('admin.name') }}</th><th>{{ __('admin.email') }}</th><th>{{ __('admin.role') }}</th><th>{{ __('admin.status') }}</th><th></th></tr></thead>
        <tbody>
        @forelse($users as $user)
            <tr>
                <td class="font-bold">{{ $user->name }}</td>
                <td dir="ltr">{{ $user->email }}</td>
                <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                <td><x-status-badge :status="$user->is_active ? 'active' : 'inactive'" /></td>
                <td class="space-x-2 space-x-reverse">
                    <a class="font-bold text-brand-700" href="{{ route('admin.users.edit', $user) }}">{{ __('admin.edit') }}</a>
                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="inline">@csrf<button class="font-bold text-amber-700">{{ __('admin.reset_password') }}</button></form>
                    @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">@csrf @method('DELETE')<button class="font-bold text-red-700">{{ __('admin.delete') }}</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty-state /></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-pagination :paginator="$users" />
</x-card>
@endsection
