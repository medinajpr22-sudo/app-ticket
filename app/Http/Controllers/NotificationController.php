<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    /**
     * Open a notification: it counts as read and leads to its ticket.
     */
    public function update(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return is_string($url) && $this->isInternalPath($url)
            ? redirect($url)
            : to_route('dashboard');
    }

    /**
     * Mark every notification of the person as read.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    /**
     * Notifications only ever point inside the application.
     */
    private function isInternalPath(string $url): bool
    {
        return Str::startsWith($url, '/') && ! Str::startsWith($url, ['//', '/\\']);
    }
}
