<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
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

    public function loadNotifications(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user) {
            $this->unreadCount = 0;
            $this->notifications = collect();

            return;
        }

        $this->unreadCount = $user->unreadNotifications()->count();
        $this->notifications = $user->notifications()
            ->latest()
            ->limit(10)
            ->get();
    }

    public function markAsRead(string $id): void
    {
        /** @var User $user */
        $user = Auth::user();

        $notification = $user?->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications();
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
