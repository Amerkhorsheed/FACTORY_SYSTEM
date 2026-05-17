<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    /** @var Collection|DatabaseNotification[] */
    public $notifications;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    #[On('echo:private-user.{user_id},.Illuminate\\\\Notifications\\\\Events\\\\BroadcastNotificationCreated')]
    public function loadNotifications(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user) {
            $this->unreadCount = $user->unreadNotifications()->count();
            $this->notifications = $user->notifications()->take(5)->get();
        }
    }

    public function markAsRead(string $id): void
    {
        /** @var User $user */
        $user = Auth::user();

        $notification = $user?->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications();

            // Dispatch an event so the frontend can redirect if needed,
            // or we could return a redirect directly if the notification has a link.
        }
    }

    public function markAllAsRead(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $user?->unreadNotifications->markAsRead();
        $this->loadNotifications();
    }

    public function render(): View
    {
        return view('livewire.notification-bell');
    }
}
