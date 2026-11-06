<?php

namespace App\Http\Controllers;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Landing page after signing in. People who configure the platform
     * also see a summary of it.
     */
    public function __invoke(Request $request): Response
    {
        $canManagePlatform = $request->user()->can(PermissionSlug::ManageUsers->value);

        return Inertia::render('Dashboard', [
            'stats' => $canManagePlatform ? [
                'users' => User::query()->count(),
                'active_users' => User::query()->active()->count(),
                'staff' => User::query()->staff()->active()->count(),
                'teams' => Team::query()->active()->count(),
            ] : null,
        ]);
    }
}
