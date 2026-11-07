<?php

namespace Tests\Feature\Domain\Ticketing;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Actions\ReplyToTicket;
use App\Domain\Ticketing\Actions\TransferTicket;
use App\Domain\Ticketing\Enums\TicketActivity;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Notifications\TicketActivityNotification;
use Database\Seeders\MessageTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $agent;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->team = Team::factory()->create(['name' => 'Redes y Conectividad']);
        $this->agent = User::factory()->admin()->create(['name' => 'Luis Pardo']);
        $this->agent->teams()->attach($this->team);
        $this->requester = User::factory()->create(['name' => 'Carolina Méndez']);
    }

    public function test_an_answer_from_it_tells_the_requester_they_have_a_new_message(): void
    {
        Notification::fake();
        $ticket = $this->takenTicket();

        app(ReplyToTicket::class)->handle($ticket, $this->agent, '¿Puedes reiniciar el router?');

        Notification::assertSentTo(
            $this->requester,
            TicketActivityNotification::class,
            fn (TicketActivityNotification $notification): bool => $notification->activity === TicketActivity::NewMessage
                && $notification->actorName === 'Luis Pardo',
        );
        Notification::assertNotSentTo($this->agent, TicketActivityNotification::class);
    }

    public function test_an_answer_from_the_requester_reaches_the_agent_who_has_the_ticket(): void
    {
        Notification::fake();
        $ticket = $this->takenTicket();

        app(ReplyToTicket::class)->handle($ticket, $this->requester, 'Ya lo reinicié y sigue igual.');

        Notification::assertSentTo(
            $this->agent,
            TicketActivityNotification::class,
            fn (TicketActivityNotification $notification): bool => $notification->activity === TicketActivity::RequesterReplied,
        );
        Notification::assertNotSentTo($this->requester, TicketActivityNotification::class);
    }

    public function test_the_requester_hears_when_it_proposes_a_solution(): void
    {
        Notification::fake();
        $ticket = $this->takenTicket();

        app(ChangeTicketStatus::class)->handle($ticket, TicketStatus::Resolved, $this->agent);

        Notification::assertSentTo(
            $this->requester,
            TicketActivityNotification::class,
            fn (TicketActivityNotification $notification): bool => $notification->activity === TicketActivity::Resolved,
        );
    }

    public function test_status_changes_that_do_not_concern_the_requester_are_silent(): void
    {
        Notification::fake();
        $ticket = $this->takenTicket();

        app(ChangeTicketStatus::class)->handle($ticket, TicketStatus::WaitingOnRequester, $this->agent);
        app(ChangeTicketStatus::class)->handle($ticket, TicketStatus::InProgress, $this->requester);
        app(ChangeTicketStatus::class)->handle($ticket, TicketStatus::Resolved, $this->agent);
        app(ChangeTicketStatus::class)->handle($ticket, TicketStatus::InProgress, $this->requester);

        Notification::assertSentToTimes($this->requester, TicketActivityNotification::class, 2);
    }

    public function test_the_requester_learns_which_team_continues_after_a_transfer(): void
    {
        Notification::fake();
        $ticket = $this->takenTicket();
        $infrastructure = Team::factory()->create(['name' => 'Infraestructura']);

        app(TransferTicket::class)->handle($ticket, $infrastructure, $this->agent, 'Es un problema del equipo');

        Notification::assertSentTo(
            $this->requester,
            TicketActivityNotification::class,
            fn (TicketActivityNotification $notification): bool => $notification->activity === TicketActivity::Transferred,
        );
    }

    public function test_taking_a_ticket_leaves_the_requester_an_unread_notification_in_the_app(): void
    {
        $this->seed(MessageTemplateSeeder::class);
        $ticket = Ticket::factory()->for($this->requester, 'requester')->create(['team_id' => $this->team->id]);

        $this->actingAs($this->agent)->post(route('desk.tickets.take', $ticket));

        $notification = $this->requester->unreadNotifications()->sole();

        $this->assertSame('Tienes un nuevo mensaje', $notification->data['title']);
        $this->assertSame("Tu ticket #{$ticket->id} tiene una nueva respuesta de Luis Pardo.", $notification->data['body']);
        $this->assertSame($ticket->id, $notification->data['ticket_id']);
        $this->assertSame(route('portal.tickets.show', $ticket, false), $notification->data['url']);
    }

    public function test_emails_are_only_sent_when_they_are_enabled(): void
    {
        $notification = new TicketActivityNotification($this->takenTicket(), TicketActivity::NewMessage, 'Luis Pardo', 'Hola');

        config(['helpdesk.notifications.mail' => false]);
        $this->assertSame(['database'], $notification->via($this->requester));

        config(['helpdesk.notifications.mail' => true]);
        $this->assertSame(['database', 'mail'], $notification->via($this->requester));

        $mail = $notification->toMail($this->requester);
        $this->assertSame('Tienes un nuevo mensaje · #'.$notification->ticket->id, $mail->subject);
        $this->assertSame(route('portal.tickets.show', $notification->ticket), $mail->actionUrl);
    }

    private function takenTicket(): Ticket
    {
        return Ticket::factory()->for($this->requester, 'requester')->takenBy($this->agent)->create(['team_id' => $this->team->id]);
    }
}
