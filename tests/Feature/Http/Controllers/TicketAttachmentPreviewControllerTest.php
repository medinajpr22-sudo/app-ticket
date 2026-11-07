<?php

namespace Tests\Feature\Http\Controllers;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketAttachment;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketAttachmentPreviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $this->ticket = Ticket::factory()->create();
    }

    public function test_an_image_of_the_conversation_is_shown_inside_the_page(): void
    {
        $image = $this->attach('captura.png', 'image/png');

        $response = $this->actingAs($this->ticket->requester)->get(route('attachments.preview', $image));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_files_that_are_not_pictures_are_never_shown_inline(): void
    {
        $document = $this->attach('informe.pdf', 'application/pdf');

        $this->actingAs($this->ticket->requester)
            ->get(route('attachments.preview', $document))
            ->assertNotFound();
    }

    public function test_only_people_who_can_read_the_message_see_the_image(): void
    {
        $image = $this->attach('captura.png', 'image/png');

        $this->actingAs(User::factory()->create())
            ->get(route('attachments.preview', $image))
            ->assertForbidden();
    }

    public function test_the_conversation_offers_a_preview_only_for_pictures(): void
    {
        $this->attach('captura.png', 'image/png');
        $this->attach('informe.pdf', 'application/pdf');

        $this->actingAs($this->ticket->requester)
            ->get(route('portal.tickets.show', $this->ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->where('ticket.messages.0.attachments.0.preview_url', route('attachments.preview', TicketAttachment::query()->where('original_name', 'captura.png')->sole()))
                ->where('ticket.messages.1.attachments.0.preview_url', null));
    }

    private function attach(string $name, string $mimeType): TicketAttachment
    {
        $message = $this->ticket->messages()->create([
            'author_id' => $this->ticket->requester_id,
            'type' => TicketMessageType::Reply,
            'body' => 'Te envío el archivo',
        ]);

        $path = "tickets/{$this->ticket->id}/{$name}";
        Storage::disk('local')->put($path, 'contenido');

        return $message->attachments()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => $name,
            'mime_type' => $mimeType,
            'size_bytes' => 9,
        ]);
    }
}
