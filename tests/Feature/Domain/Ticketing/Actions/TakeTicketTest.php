<?php

namespace Tests\Feature\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\TakeTicket;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TakeTicketTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Both agents loaded the same unassigned ticket; the second click must lose.
     */
    public function test_the_second_of_two_simultaneous_takes_is_rejected(): void
    {
        $ticket = Ticket::factory()->create();
        $staleCopy = Ticket::query()->findOrFail($ticket->id);
        $first = User::factory()->admin()->create(['name' => 'Ana Torres']);
        $second = User::factory()->admin()->create();

        app(TakeTicket::class)->handle($ticket, $first);

        try {
            app(TakeTicket::class)->handle($staleCopy, $second);
            $this->fail('The second take should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame('Ana Torres ya tomó este ticket.', $exception->errors()['ticket'][0]);
        }

        $this->assertTrue($ticket->refresh()->assignee()->is($first));
        $this->assertDatabaseCount('ticket_assignments', 1);
    }
}
