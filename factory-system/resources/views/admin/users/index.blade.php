@extends('layouts.app')
@section('title', __('admin.users'))
@section('page-title', __('admin.users'))

@section('content')
<x-page-header :title="__('admin.users')">
    <x-btn :href="route('admin.users.create')">{{ __('admin.create_user') }}</x-btn>
</x-page-header>

<x-card>
    <x-filter-panel :action="route('admin.users.index')" :reset="route('admin.users.index')">
        <x-form-input name="search" :value="request('search')" :label="__('ui.actions.search')" placeholder="{{ __('admin.name') }} / {{ __('admin.email') }}" />
        <x-form-select name="role" :label="__('admin.role')">
            <option value="">{{ __('ui.labels.all') }}</option>
            @foreach($roles as $role)
                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>
            @endforeach
        </x-form-select>
    </x-filter-panel>

    <div class="table-scroll"><table class="table">
        <thead>
            <tr>
                <th scope="col">{{ __('admin.name') }}</th>
                <th scope="col">{{ __('admin.email') }}</th>
                <th scope="col">{{ __('admin.role') }}</th>
                <th scope="col">{{ __('admin.status') }}</th>
                <th scope="col" class="table-actions">{{ __('ui.labels.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($users as $user)
            <tr>
                <td class="font-bold text-slate-900">{{ $user->name }}</td>
                <td dir="ltr">{{ $user->email }}</td>
                <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                <td><x-status-badge :status="$user->is_active ? 'active' : 'inactive'" /></td>
                <td class="table-actions space-x-1 space-x-reverse">
                    <a class="action-link" href="{{ route('admin.users.edit', $user) }}" aria-label="{{ __('admin.edit') }} {{ $user->name }}">{{ __('admin.edit') }}</a>
                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="inline">
                        @csrf
                        <button type="submit" class="action-link action-link-warning" aria-label="{{ __('admin.reset_password') }} {{ $user->name }}">{{ __('admin.reset_password') }}</button>
                    </form>
                    @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="action-link action-link-danger" aria-label="{{ __('admin.delete') }} {{ $user->name }}">{{ __('admin.delete') }}</button>
                        </form>
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
