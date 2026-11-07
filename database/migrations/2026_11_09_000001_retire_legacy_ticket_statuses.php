<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "New" is now called "received", and "waiting on a third party" and
 * "cancelled" are no longer used. Tickets in a retired status move to the
 * closest current one, and the move is written to their status history.
 * Earlier history rows keep the retired values: they are what happened.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('tickets')->where('status', 'new')->update(['status' => 'received']);
            DB::table('ticket_status_changes')->where('from_status', 'new')->update(['from_status' => 'received']);
            DB::table('ticket_status_changes')->where('to_status', 'new')->update(['to_status' => 'received']);

            $this->moveTickets('waiting_on_third_party', fn (object $ticket): array => [
                'status' => $ticket->assignee_id === null ? 'received' : 'in_progress',
            ]);

            $this->moveTickets('cancelled', fn (object $ticket): array => [
                'status' => 'closed',
                'closed_at' => $ticket->closed_at ?? now(),
            ]);
        });
    }

    /**
     * Reverse the migrations. Tickets moved out of a retired status stay where
     * they are: going back would need knowing which ones were moved.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('tickets')->where('status', 'received')->update(['status' => 'new']);
            DB::table('ticket_status_changes')->where('from_status', 'received')->update(['from_status' => 'new']);
            DB::table('ticket_status_changes')->where('to_status', 'received')->update(['to_status' => 'new']);
        });
    }

    /**
     * @param  Closure(object): array{status: string}  $attributesFor
     */
    private function moveTickets(string $retiredStatus, Closure $attributesFor): void
    {
        DB::table('tickets')
            ->where('status', $retiredStatus)
            ->lazyById()
            ->each(function (object $ticket) use ($retiredStatus, $attributesFor): void {
                $attributes = $attributesFor($ticket);

                DB::table('tickets')->where('id', $ticket->id)->update([...$attributes, 'updated_at' => now()]);

                DB::table('ticket_status_changes')->insert([
                    'ticket_id' => $ticket->id,
                    'user_id' => null,
                    'from_status' => $retiredStatus,
                    'to_status' => $attributes['status'],
                    'created_at' => now(),
                ]);
            });
    }
};
