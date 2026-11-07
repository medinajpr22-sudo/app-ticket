<?php

namespace App\Http\Middleware;

use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * How many notifications the bell shows.
     */
    private const RECENT_NOTIFICATIONS = 8;

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing(['role.permissions', 'teams']);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? UserResource::make($user) : null,
                'permissions' => $user?->permissionSlugs() ?? [],
            ],
            'notifications' => fn (): ?array => $user ? [
                'unread_count' => $user->unreadNotifications()->count(),
                'recent' => NotificationResource::collection(
                    $user->notifications()->latest()->limit(self::RECENT_NOTIFICATIONS)->get(),
                ),
            ] : null,
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
            ],
        ];
    }
}
