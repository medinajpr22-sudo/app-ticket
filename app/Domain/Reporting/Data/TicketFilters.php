<?php

namespace App\Domain\Reporting\Data;

use App\Domain\Ticketing\Enums\TicketStatus;

/**
 * Which tickets a report counts: the ones created in the period, narrowed
 * down by the requester's area, the team and the status.
 */
final readonly class TicketFilters
{
    /**
     * @param  list<int>|null  $teamIds  The teams the viewer may see; null for all of them.
     * @param  TicketStatus|'open'|null  $status  A single status, every open one, or any.
     */
    public function __construct(
        public ReportPeriod $period,
        public ?array $teamIds = null,
        public ?int $teamId = null,
        public ?int $departmentId = null,
        public TicketStatus|string|null $status = null,
    ) {}
}
