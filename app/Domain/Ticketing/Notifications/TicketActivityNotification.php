<?php

namespace App\Domain\Ticketing\Notifications;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketActivity;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells a person about something that happened in a ticket, inside the
 * application and, when enabled, by email.
 *
 * The in-app copy is written right away; the email goes through the queue
 * so a slow mail server never holds up the person who acted.
 */
class TicketActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private const EXCERPT_LENGTH = 140;

    public function __construct(
        public Ticket $ticket,
        public TicketActivity $activity,
        public ?string $actorName = null,
        public ?string $message = null,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return config('helpdesk.notifications.mail') ? ['database', 'mail'] : ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'activity' => $this->activity->value,
            'title' => $this->activity->title(),
            'body' => $this->body(),
            'excerpt' => $this->excerpt(),
            'url' => $this->ticketUrl($notifiable, absolute: false),
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("{$this->activity->title()} · #{$this->ticket->id}")
            ->greeting(__('Hello :name,', ['name' => Str::before($notifiable->name, ' ')]))
            ->line($this->body());

        $excerpt = $this->excerpt();

        if ($excerpt !== null) {
            $mail->line("“{$excerpt}”");
        }

        return $mail->action(__('View ticket'), $this->ticketUrl($notifiable));
    }

    private function body(): string
    {
        return $this->activity->describe($this->ticket->id, $this->actorName, $this->ticket->team->name);
    }

    private function excerpt(): ?string
    {
        return $this->message === null ? null : Str::limit(Str::squish($this->message), self::EXCERPT_LENGTH);
    }

    /**
     * Requesters follow their tickets in the portal; IT works them in the desk.
     */
    private function ticketUrl(User $notifiable, bool $absolute = true): string
    {
        $routeName = $this->ticket->requester_id === $notifiable->id ? 'portal.tickets.show' : 'desk.tickets.show';

        return route($routeName, $this->ticket, $absolute);
    }
}
