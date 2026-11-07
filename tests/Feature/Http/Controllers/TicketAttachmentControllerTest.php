<?php

namespace Tests\Feature\Http\Controllers;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Actions\ReplyToTicket;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketAttachment;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ServiceLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, ServiceLevelSeeder::class]);
        Storage::fake('local');
    }

    public function test_files_sent_with_a_new_ticket_are_stored_privately_and_downloadable_by_the_requester(): void
    {
        $requester = User::factory()->create();

        $this->actingAs($requester)->post(route('portal.tickets.store'), [
            'category_id' => Category::factory()->create()->id,
            'subject' => 'La impresora no imprime',
            'description' => 'Adjunto el error.',
            'priority' => TicketPriority::Medium->value,
            'attachments' => [UploadedFile::fake()->create('error.pdf', 120, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        $attachment = TicketAttachment::query()->sole();

        Storage::disk('local')->assertExists($attachment->path);
        $this->assertSame('error.pdf', $attachment->original_name);

        $this->actingAs($requester)
            ->get(route('attachments.show', $attachment))
            ->assertOk()
            ->assertDownload('error.pdf');
    }

    public function test_someone_else_cannot_download_the_file(): void
    {
        $attachment = $this->attachmentOnTicket(Ticket::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('attachments.show', $attachment))
            ->assertForbidden();
    }

    public function test_files_of_internal_notes_are_only_for_it(): void
    {
        $team = Team::factory()->create();
        $agent = User::factory()->admin()->create();
        $agent->teams()->attach($team);
        $ticket = Ticket::factory()->takenBy($agent)->create(['team_id' => $team->id]);

        app(ReplyToTicket::class)->handle($ticket, $agent, 'Log del servidor', isInternalNote: true, attachments: [
            UploadedFile::fake()->create('servidor.txt', 4, 'text/plain'),
        ]);
        $attachment = TicketAttachment::query()->sole();

        $this->actingAs($ticket->requester)->get(route('attachments.show', $attachment))->assertForbidden();
        $this->actingAs($agent)->get(route('attachments.show', $attachment))->assertOk();
    }

    public function test_executable_files_are_rejected(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->requester)
            ->post(route('portal.tickets.messages.store', $ticket), [
                'body' => 'Te envío el instalador',
                'attachments' => [UploadedFile::fake()->create('instalador.exe', 50, 'application/x-msdownload')],
            ])
            ->assertSessionHasErrors(['attachments.0']);

        $this->assertDatabaseCount('ticket_messages', 0);
    }

    private function attachmentOnTicket(Ticket $ticket): TicketAttachment
    {
        app(ReplyToTicket::class)->handle($ticket, $ticket->requester, 'Adjunto captura', attachments: [
            UploadedFile::fake()->create('captura.pdf', 50, 'application/pdf'),
        ]);

        return TicketAttachment::query()->sole();
    }
}
