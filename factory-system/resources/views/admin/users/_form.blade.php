@csrf
<div class="grid gap-4 sm:grid-cols-2">
    <x-form-input name="name" :label="__('admin.name')" :value="$user->name ?? null" required />
    <x-form-input name="email" :label="__('admin.email')" type="email" :value="$user->email ?? null" required />
    <x-form-input name="phone" :label="__('admin.phone')" :value="$user->phone ?? null" />
    <x-form-select name="role" :label="__('admin.role')" required>
        @foreach($roles as $role)
            <option value="{{ $role->name }}" @selected(old('role', isset($user) ? $user->roles->first()?->name : '') === $role->name)>{{ $role->name }}</option>
        @endforeach
    </x-form-select>
    <x-form-input name="password" :label="__('admin.password')" type="password" :required="! isset($user)" />
    <x-form-input name="password_confirmation" :label="__('admin.password')" type="password" :required="! isset($user)" />
</div>
@isset($user)
    <input type="hidden" name="is_active" value="0">
    <label class="mt-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-slate-300 text-brand-600">
        {{ __('admin.active') }}
    </label>
@endisset
<x-btn type="submit" class="mt-5">{{ __('admin.save') }}</x-btn>
