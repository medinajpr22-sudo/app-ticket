<?php

namespace App\Http\Controllers\Desk;

use App\Domain\Identity\Actions\CreateUser;
use App\Domain\Identity\Enums\RoleSlug;
use App\Domain\Identity\Models\Role;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Location;
use App\Domain\Organization\Models\Team;
use App\Http\Controllers\Controller;
use App\Http\Requests\Desk\StoreTeamMemberRequest;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\LocationResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamMemberController extends Controller
{
    /**
     * Show the form a lead uses to add an assistant to their team.
     */
    public function create(Team $team): Response
    {
        Gate::authorize('lead', $team);

        $role = Role::findBySlug(RoleSlug::Admin);

        return Inertia::render('Desk/Teams/Members/Create', [
            'team' => ['id' => $team->id, 'name' => $team->name],
            'role' => ['label' => $role->slug->label(), 'description' => $role->slug->description()],
            'departments' => DepartmentResource::collection(Department::query()->orderBy('name')->get()),
            'locations' => LocationResource::collection(Location::query()->orderBy('name')->get()),
        ]);
    }

    /**
     * Create the assistant as IT staff and add them to the team. They start
     * as a regular member; making someone a lead stays with the super admin.
     */
    public function store(StoreTeamMemberRequest $request, Team $team, CreateUser $createUser): RedirectResponse
    {
        $member = $createUser->handle([
            ...$request->validated(),
            'role_id' => Role::findBySlug(RoleSlug::Admin)->id,
            'is_active' => true,
            'team_ids' => [$team->id],
        ]);

        return to_route('desk.teams.show', $team)
            ->with('success', __(':name joined :team.', ['name' => $member->name, 'team' => $team->name]));
    }
}
