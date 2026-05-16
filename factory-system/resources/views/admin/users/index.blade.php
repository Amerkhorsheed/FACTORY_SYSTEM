<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>{{ __('admin.users') }}</title></head>
<body>
<h1>{{ __('admin.users') }}</h1>
<a href="{{ route('admin.users.create') }}">{{ __('admin.create_user') }}</a>
<form method="GET" action="{{ route('admin.users.index') }}">
    <input name="search" value="{{ request('search') }}" placeholder="{{ __('admin.name') }}">
    <select name="role">
        <option value="">{{ __('admin.role') }}</option>
        @foreach($roles as $role)
            <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>
        @endforeach
    </select>
    <button type="submit">{{ __('admin.save') }}</button>
</form>
<table>
    <thead>
    <tr>
        <th>{{ __('admin.name') }}</th>
        <th>{{ __('admin.email') }}</th>
        <th>{{ __('admin.phone') }}</th>
        <th>{{ __('admin.role') }}</th>
        <th>{{ __('admin.status') }}</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @forelse($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td dir="ltr">{{ $user->email }}</td>
            <td>{{ $user->phone }}</td>
            <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
            <td>{{ $user->is_active ? __('admin.active') : __('admin.inactive') }}</td>
            <td>
                <a href="{{ route('admin.users.edit', $user) }}">{{ __('admin.edit') }}</a>
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" style="display:inline">
                    @csrf
                    <button type="submit">{{ __('admin.reset_password') }}</button>
                </form>
                @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit">{{ __('admin.delete') }}</button>
                    </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6">{{ __('admin.users') }}</td></tr>
    @endforelse
    </tbody>
</table>
{{ $users->links() }}
</body>
</html>
