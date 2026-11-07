import Card from '@/Components/ui/Card';
import ConfirmDialog from '@/Components/ui/ConfirmDialog';
import Icon from '@/Components/ui/Icon';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import SecondaryButton from '@/Components/ui/SecondaryButton';
import { formatDateTime } from '@/lib/formatDate';
import { Ticket } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export interface TicketClosureAbilities {
    close: boolean;
    reopen: boolean;
    archive: boolean;
    unarchive: boolean;
}

type PendingAction = 'close' | 'archive' | null;

/**
 * Lets the requester finish their own ticket: confirm it is solved, or take it
 * off their list. Both ask first. Once closed, it says who closed it and when.
 */
export default function TicketClosureCard({ ticket, can }: { ticket: Ticket; can: TicketClosureAbilities }) {
    const [pending, setPending] = useState<PendingAction>(null);
    const [processing, setProcessing] = useState(false);
    const isClosed = ticket.status.value === 'closed';

    const confirm = () => {
        const url = pending === 'close'
            ? route('portal.tickets.closure.store', ticket.id)
            : route('portal.tickets.archive.store', ticket.id);

        router.post(url, {}, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setPending(null);
            },
        });
    };

    return (
        <>
            {isClosed && (
                <Card role="status" className="border-l-[6px] border-l-success">
                    <h2 className="flex items-center gap-2 text-lg font-extrabold">
                        <Icon name="check" className="text-success" />
                        Ticket cerrado
                    </h2>
                    {ticket.closed_at && (
                        <p className="mt-1 text-sm text-muted">
                            Lo cerró <span className="font-bold text-ink">{ticket.closed_by?.name ?? 'el sistema'}</span> el{' '}
                            {formatDateTime(ticket.closed_at)}.
                        </p>
                    )}
                    {ticket.archived_at && (
                        <p className="mt-2 text-sm font-semibold text-muted">Está en tus archivados: no aparece en tu lista.</p>
                    )}
                    {can.archive && (
                        <SecondaryButton size="sm" className="mt-3" onClick={() => setPending('archive')}>
                            <Icon name="eyeOff" size={16} />
                            Quitar de mi lista
                        </SecondaryButton>
                    )}
                    {can.unarchive && (
                        <SecondaryButton
                            size="sm"
                            className="mt-3"
                            onClick={() => router.delete(route('portal.tickets.archive.destroy', ticket.id), { preserveScroll: true })}
                        >
                            <Icon name="back" size={16} />
                            Volver a mi lista
                        </SecondaryButton>
                    )}
                </Card>
            )}

            {can.close && (
                <Card aria-labelledby="closure-heading">
                    <h2 id="closure-heading" className="text-lg font-extrabold">¿Ya se resolvió?</h2>
                    <p className="mb-3 text-sm text-muted">
                        {ticket.status.value === 'resolved'
                            ? `${ticket.assignee?.name ?? 'TI'} marcó tu caso como resuelto. Confírmalo para cerrarlo.`
                            : 'Si ya no necesitas ayuda, puedes cerrar el ticket tú mismo.'}
                    </p>
                    <div className="flex flex-col gap-2">
                        <PrimaryButton type="button" size="sm" onClick={() => setPending('close')}>
                            <Icon name="check" size={16} />
                            Marcar como resuelto
                        </PrimaryButton>
                        {can.archive && (
                            <SecondaryButton size="sm" onClick={() => setPending('archive')}>
                                <Icon name="eyeOff" size={16} />
                                Cerrar y quitar de mi lista
                            </SecondaryButton>
                        )}
                        {can.reopen && (
                            <SecondaryButton
                                size="sm"
                                onClick={() => router.delete(route('portal.tickets.resolution.destroy', ticket.id), { preserveScroll: true })}
                            >
                                No, el problema sigue
                            </SecondaryButton>
                        )}
                    </div>
                </Card>
            )}

            <ConfirmDialog
                show={pending === 'close'}
                title="¿Cerrar el ticket como resuelto?"
                confirmLabel="Sí, está resuelto"
                processing={processing}
                onConfirm={confirm}
                onClose={() => setPending(null)}
            >
                El ticket quedará cerrado a tu nombre y TI dejará de trabajar en él. Si el problema vuelve, crea un ticket nuevo.
            </ConfirmDialog>

            <ConfirmDialog
                show={pending === 'archive'}
                title="¿Quitar el ticket de tu lista?"
                confirmLabel="Sí, quitarlo"
                processing={processing}
                onConfirm={confirm}
                onClose={() => setPending(null)}
            >
                {isClosed
                    ? 'Dejará de aparecer en tu lista. No se borra: lo encuentras en Archivados cuando quieras.'
                    : 'El ticket se cerrará a tu nombre y dejará de aparecer en tu lista. No se borra: lo encuentras en Archivados cuando quieras.'}
            </ConfirmDialog>
        </>
    );
}
