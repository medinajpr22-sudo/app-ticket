<?php

namespace Tests\Feature\Http\Controllers\Management;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Models\MessageTemplate;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_without_template_management_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('management.message-templates.index'))
            ->assertForbidden();
    }

    public function test_store_creates_a_canned_response_and_records_its_author(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $team = Team::factory()->create();

        $this->actingAs($superAdmin)
            ->post(route('management.message-templates.store'), $this->payload(['team_id' => $team->id]))
            ->assertRedirect(route('management.message-templates.index'))
            ->assertSessionHas('success', 'Plantilla creada.');

        $this->assertDatabaseHas('message_templates', [
            'team_id' => $team->id,
            'author_id' => $superAdmin->id,
            'purpose' => MessageTemplatePurpose::CannedResponse->value,
            'name' => 'Pedir captura',
        ]);
    }

    public function test_store_rejects_an_unknown_variable(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.message-templates.store'), $this->payload([
                'body' => 'Hola {{requester.nickname}}, ¿podrías enviar una captura?',
            ]))
            ->assertSessionHasErrors(['body' => 'La variable {{requester.nickname}} no existe.']);
    }

    public function test_store_rejects_the_agent_name_in_an_acknowledgement(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.message-templates.store'), $this->payload([
                'purpose' => MessageTemplatePurpose::Acknowledgement->value,
                'body' => 'Recibimos tu ticket. Te atiende {{agent.name}}.',
            ]))
            ->assertSessionHasErrors(['body' => 'La variable {{agent.name}} no está disponible en este tipo de mensaje.']);
    }

    public function test_store_rejects_a_second_active_greeting_for_all_teams(): void
    {
        MessageTemplate::factory()->purpose(MessageTemplatePurpose::Greeting)->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.message-templates.store'), $this->payload([
                'purpose' => MessageTemplatePurpose::Greeting->value,
                'name' => 'Otro saludo',
            ]))
            ->assertSessionHasErrors([
                'is_active' => 'Ya hay otra plantilla activa de este tipo para todos los equipos. Desactívala primero.',
            ]);
    }

    public function test_store_allows_a_team_greeting_next_to_the_general_one(): void
    {
        MessageTemplate::factory()->purpose(MessageTemplatePurpose::Greeting)->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.message-templates.store'), $this->payload([
                'purpose' => MessageTemplatePurpose::Greeting->value,
                'team_id' => Team::factory()->create()->id,
                'name' => 'Saludo de Dialisoft',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('message_templates', 2);
    }

    public function test_update_keeps_the_original_author(): void
    {
        $author = User::factory()->admin()->create();
        $template = MessageTemplate::factory()->create(['author_id' => $author->id]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.message-templates.update', $template), $this->payload(['is_active' => false]))
            ->assertRedirect(route('management.message-templates.index'));

        $template->refresh();

        $this->assertSame($author->id, $template->author_id);
        $this->assertSame('Pedir captura', $template->name);
        $this->assertFalse($template->is_active);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'purpose' => MessageTemplatePurpose::CannedResponse->value,
            'team_id' => null,
            'name' => 'Pedir captura',
            'body' => 'Hola {{requester.first_name}}, ¿podrías compartir una captura del error?',
            'is_active' => true,
            ...$overrides,
        ];
    }
}
