@csrf
<label>{{ __('admin.name') }} <input name="name" value="{{ old('name', $user->name ?? '') }}" required></label>
<label>{{ __('admin.email') }} <input name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required></label>
<label>{{ __('admin.phone') }} <input name="phone" value="{{ old('phone', $user->phone ?? '') }}"></label>
<label>{{ __('admin.role') }}
    <select name="role" required>
        @foreach($roles as $role)
            <option value="{{ $role->name }}" @selected(old('role', isset($user) ? $user->roles->first()?->name : '') === $role->name)>
                {{ $role->name }}
            </option>
        @endforeach
    </select>
</label>
<label>{{ __('admin.password') }} <input name="password" type="password" @empty($user) required @endempty></label>
<label>{{ __('admin.password') }} <input name="password_confirmation" type="password" @empty($user) required @endempty></label>
@isset($user)
    <input type="hidden" name="is_active" value="0">
    <label><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $user->is_active))> {{ __('admin.active') }}</label>
@endisset
<button type="submit">{{ __('admin.save') }}</button>
