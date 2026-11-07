<?php

namespace App\Http\Controllers;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Send each person to where they work. People who configure the
     * platform get an overview of it instead.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->can(PermissionSlug::ManageUsers->value)) {
            return Inertia::render('Dashboard', [
                'stats' => [
                    'open_tickets' => Ticket::query()->open()->count(),
                    'unassigned_tickets' => Ticket::query()->open()->whereNull('assignee_id')->count(),
                    'overdue_tickets' => $this->overdueTickets()->count(),
                    'active_users' => User::query()->active()->count(),
                    'staff' => User::query()->staff()->active()->count(),
                    'teams' => Team::query()->active()->count(),
                ],
            ]);
        }

        if ($user->can(PermissionSlug::ViewTeamTickets->value)) {
            return to_route('desk.tickets.index');
        }

        return to_route('portal.tickets.index');
    }

    /**
     * Open tickets past the deadline that applies to them right now.
     *
     * @return Builder<Ticket>
     */
    private function overdueTickets(): Builder
    {
        return Ticket::query()
            ->open()
            ->whereNull('sla_paused_at')
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->whereNull('first_responded_at')->where('first_response_due_at', '<', now()))
                ->orWhere('resolution_due_at', '<', now()));
    }
}
