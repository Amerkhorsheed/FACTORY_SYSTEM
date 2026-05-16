<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $users) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = $this->users->list($request->only(['search', 'role', 'is_active']));
        $roles = $this->users->staffRoles();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $roles = $this->users->staffRoles();

        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = $this->users->create($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('admin.user_created', ['name' => $user->name]));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $roles = $this->users->staffRoles();
        $user = $this->users->loadForEdit($user);

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->users->update($user, $request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('admin.user_updated'));
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->users->delete($user, $request->user());

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('admin.user_deleted'));
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $this->users->resetPassword($user);

        return back()->with('success', __('admin.password_reset_sent'));
    }
}
