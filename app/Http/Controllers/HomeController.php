<?php

namespace App\Http\Controllers;

use App\Domain\Identity\Enums\PermissionSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Send each person to where they work. Those who oversee the whole help
     * desk get its statistics; IT staff get the inbox of their teams; everyone
     * else follows their own tickets.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->can(PermissionSlug::ViewReports->value) && $user->can(PermissionSlug::ViewAllTickets->value)) {
            return to_route('reports.overview');
        }

        if ($user->can(PermissionSlug::ViewTeamTickets->value) && $user->teams()->exists()) {
            return to_route('desk.tickets.index');
        }

        return to_route('portal.tickets.index');
    }
}
