import Badge, { BadgeTone } from '@/Components/ui/Badge';
import { IconName } from '@/Components/ui/Icon';
import { formatRelative } from '@/lib/formatDate';
import { SlaStateValue, TicketSla } from '@/types';

const appearance: Record<SlaStateValue, { tone: BadgeTone; icon: IconName }> = {
    on_track: { tone: 'success', icon: 'clock' },
    at_risk: { tone: 'warning', icon: 'warn' },
    breached: { tone: 'brand', icon: 'warn' },
    paused: { tone: 'neutral', icon: 'clock' },
    met: { tone: 'success', icon: 'check' },
    missed: { tone: 'brand', icon: 'x' },
};

/**
 * SLA state with the deadline that applies now, e.g. "En riesgo · respuesta en 30 minutos".
 */
export default function SlaBadge({ sla, showDeadline = true }: { sla: TicketSla; showDeadline?: boolean }) {
    const { tone, icon } = appearance[sla.state.value];
    const isRunning = ['on_track', 'at_risk', 'breached'].includes(sla.state.value);
    const deadline = sla.target === 'first_response' ? 'respuesta' : 'solución';

    return (
        <span className="inline-flex flex-col gap-0.5">
            <Badge tone={tone} icon={icon}>
                <span className="sr-only">SLA: </span>
                {sla.state.label}
            </Badge>
            {showDeadline && isRunning && (
                <span className="text-xs font-semibold text-muted">
                    {deadline} {formatRelative(sla.due_at)}
                </span>
            )}
        </span>
    );
}
