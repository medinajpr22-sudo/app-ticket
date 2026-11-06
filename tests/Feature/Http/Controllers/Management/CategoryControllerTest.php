<?php

namespace Tests\Feature\Http\Controllers\Management;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\Shared\Enums\Icon;
use App\Domain\Ticketing\Enums\TicketType;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_without_category_management_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('management.categories.index'))
            ->assertForbidden();
    }

    public function test_index_lists_only_the_categories_of_the_selected_team(): void
    {
        $team = Team::factory()->create();
        Category::factory()->for($team)->create(['name' => 'Red, WiFi y VPN']);
        Category::factory()->create(['name' => 'Accesos y roles en SAP']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('management.categories.index', ['team' => $team->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Management/Categories/Index')
                ->has('categories', 1)
                ->where('categories.0.name', 'Red, WiFi y VPN'));
    }

    public function test_store_creates_a_category_that_uses_the_default_policy(): void
    {
        $team = Team::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.categories.store'), $this->payload(['team_id' => $team->id]))
            ->assertRedirect(route('management.categories.index'))
            ->assertSessionHas('success', 'Categoría creada.');

        $this->assertDatabaseHas('categories', [
            'team_id' => $team->id,
            'name' => 'Red, WiFi y VPN',
            'sla_policy_id' => null,
            'default_ticket_type' => TicketType::Incident->value,
        ]);
    }

    public function test_store_rejects_a_name_already_used_by_the_same_team(): void
    {
        $team = Team::factory()->create();
        Category::factory()->for($team)->create(['name' => 'Red, WiFi y VPN']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.categories.store'), $this->payload(['team_id' => $team->id]))
            ->assertSessionHasErrors(['name' => 'Este equipo ya tiene una categoría con ese nombre.']);
    }

    public function test_store_allows_the_same_name_in_another_team(): void
    {
        Category::factory()->create(['name' => 'Red, WiFi y VPN']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.categories.store'), $this->payload(['team_id' => Team::factory()->create()->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('categories', 2);
    }

    public function test_store_rejects_an_inactive_team(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.categories.store'), $this->payload([
                'team_id' => Team::factory()->inactive()->create()->id,
            ]))
            ->assertSessionHasErrors(['team_id' => 'Elige un equipo activo.']);
    }

    public function test_update_keeps_a_category_in_its_team_after_the_team_was_disabled(): void
    {
        $team = Team::factory()->inactive()->create();
        $category = Category::factory()->for($team)->create();
        $policy = SlaPolicy::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.categories.update', $category), $this->payload([
                'team_id' => $team->id,
                'sla_policy_id' => $policy->id,
                'is_visible' => false,
            ]))
            ->assertRedirect(route('management.categories.index'));

        $category->refresh();

        $this->assertSame($policy->id, $category->sla_policy_id);
        $this->assertFalse($category->is_visible);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'team_id' => null,
            'sla_policy_id' => null,
            'name' => 'Red, WiFi y VPN',
            'description' => 'Conectividad e internet',
            'icon' => Icon::Wifi->value,
            'default_ticket_type' => TicketType::Incident->value,
            'is_visible' => true,
            'sort_order' => 1,
            ...$overrides,
        ];
    }
}
