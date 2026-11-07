<?php

namespace Tests\Feature\Database;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The migration that moved tickets out of the statuses that are no longer used.
 */
class RetireLegacyTicketStatusesTest extends TestCase
{
    use RefreshDatabase;

    public function test_tickets_leave_the_retired_statuses_and_keep_their_history(): void
    {
        $cancelled = Ticket::factory()->create(['status' => TicketStatus::Cancelled, 'closed_at' => now()->subDay()]);
        $waitingWithAgent = Ticket::factory()->takenBy(User::factory()->admin()->create())->create(['status' => TicketStatus::WaitingOnThirdParty]);
        $waitingWithoutAgent = Ticket::factory()->create(['status' => TicketStatus::WaitingOnThirdParty]);
        $cancelled->statusChanges()->create(['from_status' => TicketStatus::InProgress, 'to_status' => TicketStatus::Cancelled]);

        $this->migration()->up();

        $this->assertSame(TicketStatus::Closed, $cancelled->refresh()->status);
        $this->assertTrue($cancelled->closed_at->isYesterday());
        $this->assertSame(TicketStatus::InProgress, $waitingWithAgent->refresh()->status);
        $this->assertSame(TicketStatus::Received, $waitingWithoutAgent->refresh()->status);

        $this->assertSame(
            [['in_progress', 'cancelled'], ['cancelled', 'closed']],
            $cancelled->statusChanges()->orderBy('id')->get()
                ->map(fn ($change): array => [$change->from_status->value, $change->to_status->value])
                ->all(),
        );
        $this->assertNull($cancelled->statusChanges()->latest('id')->first()->user_id);
    }

    public function test_tickets_called_new_are_now_received(): void
    {
        $ticket = Ticket::factory()->create();
        DB::table('tickets')->where('id', $ticket->id)->update(['status' => 'new']);
        DB::table('ticket_status_changes')->insert(['ticket_id' => $ticket->id, 'from_status' => null, 'to_status' => 'new', 'created_at' => now()]);

        $this->migration()->up();

        $this->assertSame('received', DB::table('tickets')->where('id', $ticket->id)->value('status'));
        $this->assertSame('received', DB::table('ticket_status_changes')->where('ticket_id', $ticket->id)->value('to_status'));
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_09_000001_retire_legacy_ticket_statuses.php');
    }
}
