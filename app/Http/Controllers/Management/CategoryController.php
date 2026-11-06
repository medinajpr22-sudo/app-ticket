<?php

namespace App\Http\Controllers\Management;

use App\Domain\Catalog\Actions\SaveCategory;
use App\Domain\Catalog\Models\Category;
use App\Domain\Organization\Models\Team;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\Shared\Enums\Icon;
use App\Domain\Ticketing\Enums\TicketType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreCategoryRequest;
use App\Http\Requests\Management\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\SlaPolicyResource;
use App\Http\Resources\TeamResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display the categories, optionally only those of one team.
     */
    public function index(Request $request): Response
    {
        /** @var array{team?: int|string|null} $filters */
        $filters = $request->validate([
            'team' => ['nullable', 'integer', Rule::exists(Team::class, 'id')],
        ]);

        $categories = Category::query()
            ->with(['team', 'slaPolicy'])
            ->when($filters['team'] ?? null, fn (Builder $query, int|string $teamId) => $query->where('team_id', $teamId))
            ->orderBy(Team::query()->select('name')->whereColumn('teams.id', 'categories.team_id'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $defaultPolicy = SlaPolicy::findDefault();

        return Inertia::render('Management/Categories/Index', [
            'categories' => CategoryResource::collection($categories),
            'teams' => TeamResource::collection(Team::query()->orderBy('name')->get()),
            'defaultPolicy' => $defaultPolicy ? SlaPolicyResource::make($defaultPolicy) : null,
            'filters' => ['team' => (string) ($filters['team'] ?? '')],
        ]);
    }

    /**
     * Show the form for creating a category.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Management/Categories/Create', [
            'preselectedTeamId' => $request->integer('team') ?: null,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request, SaveCategory $saveCategory): RedirectResponse
    {
        $saveCategory->handle(new Category, $request->validated());

        return to_route('management.categories.index')->with('success', __('Category created.'));
    }

    /**
     * Show the form for editing the category.
     */
    public function edit(Category $category): Response
    {
        return Inertia::render('Management/Categories/Edit', [
            'category' => CategoryResource::make($category),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the category.
     */
    public function update(UpdateCategoryRequest $request, Category $category, SaveCategory $saveCategory): RedirectResponse
    {
        $saveCategory->handle($category, $request->validated());

        return to_route('management.categories.index')->with('success', __('Category updated.'));
    }

    /**
     * Choices shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'teams' => TeamResource::collection(Team::query()->orderBy('name')->get()),
            'policies' => SlaPolicyResource::collection(
                SlaPolicy::query()->with('targets')->orderByDesc('is_default')->orderBy('name')->get(),
            ),
            'icons' => Icon::options(),
            'ticketTypes' => array_map(fn (TicketType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ], TicketType::cases()),
        ];
    }
}
