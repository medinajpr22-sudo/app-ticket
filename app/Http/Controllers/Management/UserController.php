<?php

namespace App\Http\Controllers\Management;

use App\Domain\Identity\Actions\CreateUser;
use App\Domain\Identity\Actions\UpdateUser;
use App\Domain\Identity\Enums\RoleSlug;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Location;
use App\Domain\Organization\Models\Team;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreUserRequest;
use App\Http\Requests\Management\UpdateUserRequest;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\RoleResource;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display the users, filtered by search text, role and status.
     */
    public function index(Request $request): Response
    {
        /** @var array{search?: string|null, role?: string|null, status?: string|null} $filters */
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(RoleSlug::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->with(['role', 'department', 'location', 'teams'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->whereLike('name', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%"),
            ))
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->whereRelation('role', 'slug', $role))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Management/Users/Index', [
            'users' => UserResource::collection($users),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role' => $filters['role'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'roles' => RoleSlug::options(),
        ]);
    }

    /**
     * Show the form for creating a user.
     */
    public function create(): Response
    {
        return Inertia::render('Management/Users/Create', $this->formOptions());
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $createUser->handle($request->validated());

        return to_route('management.users.index')->with('success', __('User created.'));
    }

    /**
     * Show the form for editing the user.
     */
    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('Management/Users/Edit', [
            'user' => UserResource::make($user->load(['role', 'department', 'location', 'teams'])),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the user.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->handle($user, $request->validated());

        return to_route('management.users.index')->with('success', __('User updated.'));
    }

    /**
     * Choices shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'roles' => RoleResource::collection(Role::query()->orderBy('id')->get()),
            'departments' => DepartmentResource::collection(Department::query()->orderBy('name')->get()),
            'locations' => LocationResource::collection(Location::query()->orderBy('name')->get()),
            'teams' => TeamResource::collection(Team::query()->orderBy('name')->get()),
        ];
    }
}
