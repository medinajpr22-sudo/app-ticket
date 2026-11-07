import Badge, { BadgeTone } from '@/Components/ui/Badge';
import { IconName } from '@/Components/ui/Icon';
import { Option, TicketStatusValue } from '@/types';

const appearance: Record<TicketStatusValue, { tone: BadgeTone; icon: IconName }> = {
    received: { tone: 'info', icon: 'inbox' },
    in_progress: { tone: 'warning', icon: 'clock' },
    waiting_on_requester: { tone: 'neutral', icon: 'help' },
    resolved: { tone: 'success', icon: 'check' },
    closed: { tone: 'neutral', icon: 'lock' },
};

/**
 * A status no longer in use only shows up in old data; it gets a neutral look.
 */
const fallback: { tone: BadgeTone; icon: IconName } = { tone: 'neutral', icon: 'clock' };

export default function StatusBadge({ status }: { status: Option<TicketStatusValue> }) {
    const { tone, icon } = appearance[status.value] ?? fallback;

    return (
        <Badge tone={tone} icon={icon}>
            {status.label}
        </Badge>
    );
}
