<?php

namespace Tests\Feature\Http\Controllers;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\ReplyToTicket;
use App\Domain\Ticketing\Models\Ticket;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->agent = User::factory()->admin()->create(['name' => 'Luis Pardo']);
        $this->ticket = Ticket::factory()->takenBy($this->agent)->create();
        $this->agent->teams()->attach($this->ticket->team_id);

        app(ReplyToTicket::class)->handle($this->ticket, $this->agent, '¿Puedes reiniciar el router?');
    }

    public function test_every_page_shows_the_unread_notifications(): void
    {
        $this->actingAs($this->ticket->requester)
            ->get(route('portal.tickets.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('notifications.unread_count', 1)
                ->where('notifications.recent.0.title', 'Tienes un nuevo mensaje')
                ->where('notifications.recent.0.excerpt', '¿Puedes reiniciar el router?')
                ->where('notifications.recent.0.read_at', null));
    }

    public function test_opening_a_notification_marks_it_read_and_leads_to_the_ticket(): void
    {
        $notification = $this->ticket->requester->notifications()->sole();

        $this->actingAs($this->ticket->requester)
            ->patch(route('notifications.update', $notification->id))
            ->assertRedirect(route('portal.tickets.show', $this->ticket, false));

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_opening_the_ticket_marks_its_notifications_read(): void
    {
        $other = Ticket::factory()->takenBy($this->agent)->for($this->ticket->requester, 'requester')->create(['team_id' => $this->ticket->team_id]);
        app(ReplyToTicket::class)->handle($other, $this->agent, 'Otro tema');

        $this->actingAs($this->ticket->requester)
            ->get(route('portal.tickets.show', $this->ticket))
            ->assertInertia(fn (Assert $page) => $page->where('notifications.unread_count', 1));

        $this->assertSame([$other->id], $this->ticket->requester->unreadNotifications()->get()->pluck('data.ticket_id')->all());
    }

    public function test_marking_everything_as_read(): void
    {
        $this->actingAs($this->ticket->requester)
            ->from(route('portal.tickets.index'))
            ->delete(route('notifications.destroy'))
            ->assertRedirect(route('portal.tickets.index'));

        $this->assertSame(0, $this->ticket->requester->unreadNotifications()->count());
    }

    public function test_nobody_opens_the_notifications_of_someone_else(): void
    {
        $notification = $this->ticket->requester->notifications()->sole();

        $this->actingAs(User::factory()->create())
            ->patch(route('notifications.update', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->refresh()->read_at);
    }

    public function test_a_notification_never_redirects_outside_the_application(): void
    {
        $notification = $this->ticket->requester->notifications()->sole();
        $notification->update(['data' => [...$notification->data, 'url' => '//evil.example.com']]);

        $this->actingAs($this->ticket->requester)
            ->patch(route('notifications.update', $notification->id))
            ->assertRedirect(route('dashboard'));
    }
}
