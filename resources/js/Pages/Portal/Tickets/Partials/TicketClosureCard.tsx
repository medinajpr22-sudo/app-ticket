import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import ConfirmDialog from '@/Components/ui/ConfirmDialog';
import Icon from '@/Components/ui/Icon';
import SecondaryButton from '@/Components/ui/SecondaryButton';
import { formatDateTime } from '@/lib/formatDate';
import { PageProps, Ticket } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export interface TicketClosureAbilities {
    reopen: boolean;
    archive: boolean;
    unarchive: boolean;
}

/**
 * How the ticket ends, as the requester sees it. Only IT closes tickets.
 * While IT's solution waits for confirmation the requester can say the
 * problem continues; once closed, the ticket is final and a new problem
 * means a new ticket.
 */
export default function TicketClosureCard({ ticket, can }: { ticket: Ticket; can: TicketClosureAbilities }) {
    const { auth } = usePage<PageProps>().props;
    const [isArchiveDialogOpen, setIsArchiveDialogOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const archive = () =>
        router.post(route('portal.tickets.archive.store', ticket.id), {}, {
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setIsArchiveDialogOpen(false);
            },
        });

    if (can.reopen) {
        return (
            <Card aria-labelledby="resolution-heading">
                <h2 id="resolution-heading" className="text-lg font-extrabold">¿El problema sigue?</h2>
                <p className="mb-3 text-sm text-muted">
                    {ticket.assignee?.name ?? 'TI'} marcó tu caso como resuelto. Si todo está bien, no tienes que hacer
                    nada: TI cerrará el ticket. Si el problema sigue, avísanos.
                </p>
                <SecondaryButton
                    size="sm"
                    onClick={() => router.delete(route('portal.tickets.resolution.destroy', ticket.id), { preserveScroll: true })}
                >
                    No, el problema sigue
                </SecondaryButton>
            </Card>
        );
    }

    if (ticket.status.value !== 'closed') {
        return null;
    }

    return (
        <>
            <Card role="status" className="border-l-[6px] border-l-success">
                <h2 className="flex items-center gap-2 text-lg font-extrabold">
                    <Icon name="lock" className="text-success" />
                    Ticket cerrado
                </h2>
                {ticket.closed_at && (
                    <p className="mt-1 text-sm text-muted">
                        Lo cerró <span className="font-bold text-ink">{ticket.closed_by?.name ?? 'TI'}</span> el{' '}
                        {formatDateTime(ticket.closed_at)}.
                    </p>
                )}
                <p className="mt-2 text-sm font-semibold">
                    Un ticket cerrado no se puede reabrir. Si el problema vuelve o necesitas algo más, crea un ticket nuevo.
                </p>
                {ticket.archived_at && (
                    <p className="mt-2 text-sm font-semibold text-muted">Está en tus archivados: no aparece en tu lista.</p>
                )}
                <div className="mt-3 flex flex-col gap-2">
                    {auth.permissions.includes('tickets.create') && (
                        <ButtonLink href={route('portal.tickets.create')} size="sm">
                            <Icon name="plus" size={16} />
                            Crear un ticket nuevo
                        </ButtonLink>
                    )}
                    {can.archive && (
                        <SecondaryButton size="sm" onClick={() => setIsArchiveDialogOpen(true)}>
                            <Icon name="eyeOff" size={16} />
                            Quitar de mi lista
                        </SecondaryButton>
                    )}
                    {can.unarchive && (
                        <SecondaryButton
                            size="sm"
                            onClick={() => router.delete(route('portal.tickets.archive.destroy', ticket.id), { preserveScroll: true })}
                        >
                            <Icon name="back" size={16} />
                            Volver a mi lista
                        </SecondaryButton>
                    )}
                </div>
            </Card>

            <ConfirmDialog
                show={isArchiveDialogOpen}
                title="¿Quitar el ticket de tu lista?"
                confirmLabel="Sí, quitarlo"
                processing={processing}
                onConfirm={archive}
                onClose={() => setIsArchiveDialogOpen(false)}
            >
                Dejará de aparecer en tu lista. No se borra: lo encuentras en Archivados cuando quieras.
            </ConfirmDialog>
        </>
    );
}
