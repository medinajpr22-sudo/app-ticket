import Badge, { BadgeTone } from '@/Components/ui/Badge';
import { IconName } from '@/Components/ui/Icon';
import { TicketPriorityValue } from '@/types';

const appearance: Record<TicketPriorityValue, { tone: BadgeTone; icon: IconName }> = {
    critical: { tone: 'brand', icon: 'warn' },
    high: { tone: 'brand', icon: 'up' },
    medium: { tone: 'warning', icon: 'flat' },
    low: { tone: 'neutral', icon: 'down' },
};

export default function PriorityBadge({
    priority,
    label,
}: {
    priority: TicketPriorityValue;
    label: string;
}) {
    const { tone, icon } = appearance[priority];

    return (
        <Badge tone={tone} icon={icon}>
            <span className="sr-only">Prioridad </span>
            {label}
        </Badge>
    );
}
