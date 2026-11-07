import Badge, { BadgeTone } from '@/Components/ui/Badge';
import { IconName } from '@/Components/ui/Icon';
import { Option, TicketStatusValue } from '@/types';

const appearance: Record<TicketStatusValue, { tone: BadgeTone; icon: IconName }> = {
    new: { tone: 'info', icon: 'inbox' },
    in_progress: { tone: 'warning', icon: 'clock' },
    waiting_on_requester: { tone: 'neutral', icon: 'help' },
    waiting_on_third_party: { tone: 'neutral', icon: 'users' },
    resolved: { tone: 'success', icon: 'check' },
    closed: { tone: 'neutral', icon: 'lock' },
    cancelled: { tone: 'neutral', icon: 'x' },
};

export default function StatusBadge({ status }: { status: Option<TicketStatusValue> }) {
    const { tone, icon } = appearance[status.value];

    return (
        <Badge tone={tone} icon={icon}>
            {status.label}
        </Badge>
    );
}
