<?php

namespace App\Http\Controllers\Management;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Actions\SaveTeam;
use App\Domain\Organization\Enums\TeamType;
use App\Domain\Organization\Models\Team;
use App\Domain\Shared\Enums\Icon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreTeamRequest;
use App\Http\Requests\Management\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * Display the teams with their members.
     */
    public function index(): Response
    {
        $teams = Team::query()
            ->with(['members' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        return Inertia::render('Management/Teams/Index', [
            'teams' => TeamResource::collection($teams),
            'types' => $this->typeOptions(),
        ]);
    }

    /**
     * Show the form for creating a team.
     */
    public function create(): Response
    {
        return Inertia::render('Management/Teams/Create', $this->formOptions(new Team));
    }

    /**
     * Store a newly created team.
     */
    public function store(StoreTeamRequest $request, SaveTeam $saveTeam): RedirectResponse
    {
        $saveTeam->handle(new Team, $request->validated());

        return to_route('management.teams.index')->with('success', __('Team created.'));
    }

    /**
     * Show the form for editing the team.
     */
    public function edit(Team $team): Response
    {
        return Inertia::render('Management/Teams/Edit', [
            'team' => TeamResource::make($team->load('members')),
            ...$this->formOptions($team),
        ]);
    }

    /**
     * Update the team.
     */
    public function update(UpdateTeamRequest $request, Team $team, SaveTeam $saveTeam): RedirectResponse
    {
        $saveTeam->handle($team, $request->validated());

        return to_route('management.teams.index')->with('success', __('Team updated.'));
    }

    /**
     * Choices shared by the create and edit forms. Current members stay
     * listed even if their account was disabled, so they can be removed.
     *
     * @return array<string, mixed>
     */
    private function formOptions(Team $team): array
    {
        $staff = User::query()
            ->staff()
            ->where(fn (Builder $query) => $query
                ->where('is_active', true)
                ->orWhereHas('teams', fn (Builder $teamQuery) => $teamQuery->whereKey($team->getKey())))
            ->with('role')
            ->orderBy('name')
            ->get();

        return [
            'types' => $this->typeOptions(),
            'icons' => Icon::options(),
            'staff' => UserResource::collection($staff),
        ];
    }

    /**
     * Team types with the explanation shown to the super admin.
     *
     * @return list<array{value: string, label: string, description: string}>
     */
    private function typeOptions(): array
    {
        return array_map(fn (TeamType $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ], TeamType::cases());
    }
}
