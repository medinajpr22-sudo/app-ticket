import AttachmentInput from '@/Components/Ticketing/AttachmentInput';
import Conversation from '@/Components/Ticketing/Conversation';
import PriorityBadge from '@/Components/Ticketing/PriorityBadge';
import SlaBadge from '@/Components/Ticketing/SlaBadge';
import StatusBadge from '@/Components/Ticketing/StatusBadge';
import TicketRatingCard from '@/Components/Ticketing/TicketRatingCard';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import ConfirmDialog from '@/Components/ui/ConfirmDialog';
import Icon from '@/Components/ui/Icon';
import InputError from '@/Components/ui/InputError';
import PageHeader from '@/Components/ui/PageHeader';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import SecondaryButton from '@/Components/ui/SecondaryButton';
import Select from '@/Components/ui/Select';
import TextArea from '@/Components/ui/TextArea';
import AppLayout from '@/Layouts/AppLayout';
import { formatDateTime, formatRelative } from '@/lib/formatDate';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { AttachmentLimits, Option, PageProps, Ticket, TicketPriorityValue, TicketStatusValue } from '@/types';
import { router, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import AssignmentCard from './Partials/AssignmentCard';
import PriorityCard from './Partials/PriorityCard';
import TransferCard from './Partials/TransferCard';

/**
 * The dialog gives focus back when its closing animation ends; the reply box
 * takes it right after, so the agent can start typing.
 */
const FOCUS_AFTER_DIALOG_MS = 300;

interface CannedResponse {
    id: number;
    name: string;
    body: string;
}

export default function Show({
    ticket,
    cannedResponses,
    statusOptions,
    priorityOptions,
    teamMembers,
    otherTeams,
    attachmentLimits,
    can,
}: {
    ticket: Ticket;
    cannedResponses: CannedResponse[];
    statusOptions: Option<TicketStatusValue>[];
    priorityOptions: Option<TicketPriorityValue>[];
    teamMembers: Array<{ id: number; name: string }>;
    otherTeams: Array<{ id: number; name: string }>;
    attachmentLimits: AttachmentLimits;
    can: {
        take: boolean;
        respond: boolean;
        changeStatus: boolean;
        close: boolean;
        changePriority: boolean;
        assign: boolean;
        transfer: boolean;
    };
}) {
    const { auth, errors } = usePage<PageProps & { errors: Record<string, string> }>().props;
    const message = useForm({ body: '', attachments: [] as File[] });
    const [status, setStatus] = useState<TicketStatusValue>(ticket.status.value);
    const [isTakeDialogOpen, setIsTakeDialogOpen] = useState(false);
    const [isTaking, setIsTaking] = useState(false);
    const [isCloseDialogOpen, setIsCloseDialogOpen] = useState(false);
    const [isChangingStatus, setIsChangingStatus] = useState(false);

    const changeStatus = () =>
        router.put(route('desk.tickets.status.update', ticket.id), { status }, {
            preserveScroll: true,
            onStart: () => setIsChangingStatus(true),
            onFinish: () => {
                setIsChangingStatus(false);
                setIsCloseDialogOpen(false);
            },
        });

    /**
     * Take the ticket from whoever the agent sees on the screen. If it changed
     * hands meanwhile, the server refuses and the page shows who has it now.
     */
    const takeTicket = () => {
        router.post(route('desk.tickets.take', ticket.id), { assignee_id: ticket.assignee?.id ?? null }, {
            preserveScroll: true,
            onStart: () => setIsTaking(true),
            onSuccess: () => {
                setIsTakeDialogOpen(false);
                window.setTimeout(() => document.getElementById('body')?.focus(), FOCUS_AFTER_DIALOG_MS);
            },
            onFinish: () => {
                setIsTaking(false);
                setIsTakeDialogOpen(false);
            },
        });
    };

    const sendMessage: FormEventHandler = (e) => {
        e.preventDefault();
        message.post(route('desk.tickets.messages.store', ticket.id), {
            ...formSubmitOptions,
            onSuccess: () => message.reset('body', 'attachments'),
        });
    };

    const deadlines: Array<[string, string]> = [
        ['Creado', formatRelative(ticket.created_at)],
        ['Primera respuesta', ticket.first_responded_at ? `Respondido ${formatRelative(ticket.first_responded_at)}` : 'Pendiente'],
        ...(ticket.closed_at
            ? [['Cerrado', `${ticket.closed_by?.name ?? 'Sistema'} · ${formatDateTime(ticket.closed_at)}`] as [string, string]]
            : []),
    ];

    return (
        <AppLayout title={`Ticket #${ticket.id}`}>
            <ButtonLink href={route('desk.tickets.index')} variant="secondary" size="sm" className="mb-4">
                <Icon name="back" size={16} />
                Bandeja
            </ButtonLink>

            <PageHeader
                title={`#${ticket.id} · ${ticket.subject}`}
                description={`${ticket.team?.name} · ${ticket.category?.name}`}
                actions={
                    <div className="flex flex-wrap items-start gap-2">
                        <StatusBadge status={ticket.status} />
                        <PriorityBadge priority={ticket.priority.value} label={ticket.priority.label} />
                        {ticket.sla && <SlaBadge sla={ticket.sla} />}
                    </div>
                }
            />

            <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_330px]">
                <Card aria-labelledby="conversation-heading">
                    <h2 id="conversation-heading" className="mb-4 text-xl font-extrabold">Conversación</h2>
                    <Conversation messages={ticket.messages ?? []} viewerId={auth.user.id} />

                    {can.respond && (
                        <form onSubmit={sendMessage} noValidate className="mt-6 border-t border-line pt-5">
                            {cannedResponses.length > 0 && (
                                <div role="group" aria-label="Respuestas rápidas" className="mb-3 flex flex-wrap gap-1.5">
                                    {cannedResponses.map((response) => (
                                        <button
                                            key={response.id}
                                            type="button"
                                            title={response.body}
                                            onClick={() => message.setData('body', response.body)}
                                            className="min-h-9 rounded-full border-[1.5px] border-field bg-white/85 px-3 text-sm font-bold text-wine hover:border-crimson"
                                        >
                                            {response.name}
                                        </button>
                                    ))}
                                </div>
                            )}

                            <label htmlFor="body" className="mb-1.5 block text-sm font-extrabold">
                                Respuesta al solicitante
                            </label>
                            <TextArea
                                id="body"
                                rows={4}
                                value={message.data.body}
                                aria-invalid={message.errors.body ? true : undefined}
                                onChange={(e) => message.setData('body', e.target.value)}
                            />
                            <InputError message={message.errors.body} />

                            <div className="mt-3">
                                <AttachmentInput
                                    files={message.data.attachments}
                                    onChange={(files) => message.setData('attachments', files)}
                                    limits={attachmentLimits}
                                    error={Object.entries(message.errors).find(([field]) => field.startsWith('attachments'))?.[1]}
                                />
                            </div>

                            <div className="mt-3 flex justify-end">
                                <PrimaryButton disabled={message.processing}>Enviar respuesta</PrimaryButton>
                            </div>
                        </form>
                    )}

                    {!can.respond && can.take && (
                        <div className="mt-6 border-t border-line pt-5">
                            <p id="locked-composer-label" className="mb-1.5 text-sm font-extrabold">Respuesta al solicitante</p>
                            <button
                                type="button"
                                aria-describedby="locked-composer-label"
                                onClick={() => setIsTakeDialogOpen(true)}
                                className="flex min-h-24 w-full items-start gap-2 rounded-2xl border-[1.5px] border-dashed border-field bg-white/60 px-4 py-3 text-left font-semibold text-muted transition hover:border-crimson hover:text-ink"
                            >
                                <Icon name="lock" size={18} className="mt-0.5 shrink-0" />
                                {ticket.assignee
                                    ? `Solo lectura: lo atiende ${ticket.assignee.name}. Toma el ticket para escribir.`
                                    : 'Solo lectura: toma el ticket para escribir.'}
                            </button>
                            <InputError message={errors.ticket} />
                        </div>
                    )}
                </Card>

                <div className="flex flex-col gap-5">
                    {can.take && !ticket.assignee && (
                        <Card className="border-l-[6px] border-l-crimson">
                            <h2 className="text-lg font-extrabold">Nadie lo ha tomado</h2>
                            <p className="mb-3 text-sm text-muted">
                                Al tomarlo, el solicitante recibe el saludo de tu equipo con tu nombre.
                            </p>
                            <PrimaryButton type="button" className="w-full" disabled={isTaking} onClick={takeTicket}>
                                <Icon name="check" />
                                Tomar ticket
                            </PrimaryButton>
                            <InputError message={errors.ticket} />
                        </Card>
                    )}

                    {can.changeStatus && (
                        <Card aria-labelledby="status-heading">
                            <h2 id="status-heading" className="mb-3 text-lg font-extrabold">Estado</h2>
                            <label htmlFor="status" className="sr-only">Nuevo estado</label>
                            <Select id="status" value={status} onChange={(e) => setStatus(e.target.value as TicketStatusValue)}>
                                {!statusOptions.some((option) => option.value === ticket.status.value) && (
                                    <option value={ticket.status.value}>{ticket.status.label}</option>
                                )}
                                {statusOptions.map((option) => (
                                    <option key={option.value} value={option.value}>{option.label}</option>
                                ))}
                            </Select>
                            {status === 'waiting_on_requester' && ticket.status.value !== status && (
                                <p className="mt-2 text-sm font-semibold text-muted">
                                    El reloj del SLA se detiene mientras esperas al usuario.
                                </p>
                            )}
                            {status === 'closed' && (
                                <p className="mt-2 text-sm font-semibold text-muted">
                                    Cerrar es definitivo: el ticket no se podrá reabrir.
                                </p>
                            )}
                            {!can.close && (
                                <p className="mt-2 text-sm font-semibold text-muted">
                                    Solo quien tiene el ticket asignado puede cerrarlo.
                                </p>
                            )}
                            <InputError message={errors.status} />
                            <SecondaryButton
                                size="sm"
                                className="mt-3 w-full"
                                disabled={status === ticket.status.value}
                                onClick={() => (status === 'closed' ? setIsCloseDialogOpen(true) : changeStatus())}
                            >
                                Cambiar estado
                            </SecondaryButton>
                        </Card>
                    )}

                    {can.changePriority && <PriorityCard ticket={ticket} options={priorityOptions} />}

                    {can.assign && <AssignmentCard ticket={ticket} members={teamMembers} />}

                    {can.transfer && otherTeams.length > 0 && <TransferCard ticket={ticket} teams={otherTeams} />}

                    {ticket.rating && <TicketRatingCard ticket={ticket} canRate={false} title="Calificación del solicitante" />}

                    <Card aria-labelledby="requester-heading">
                        <h2 id="requester-heading" className="mb-2 text-lg font-extrabold">Solicitante</h2>
                        <p className="font-extrabold">{ticket.requester?.name}</p>
                        <p className="text-sm text-muted">{ticket.requester?.job_title}</p>
                        <p className="mt-2 text-sm font-semibold">{ticket.requester?.email}</p>
                        <p className="text-sm text-muted">
                            {[ticket.requester?.department?.name, ticket.requester?.location?.name].filter(Boolean).join(' · ')}
                        </p>
                    </Card>

                    <Card aria-labelledby="details-heading">
                        <h2 id="details-heading" className="mb-2 text-lg font-extrabold">Detalles</h2>
                        <dl className="divide-y divide-line text-sm">
                            {[
                                ['Asignado a', ticket.assignee?.name ?? 'Sin asignar'],
                                ['Tipo', ticket.type.label],
                                ['Canal', ticket.channel],
                                ...deadlines,
                            ].map(([term, value]) => (
                                <div key={term} className="flex justify-between gap-3 py-2">
                                    <dt className="font-bold text-muted">{term}</dt>
                                    <dd className="text-right font-extrabold">{value}</dd>
                                </div>
                            ))}
                        </dl>
                    </Card>
                </div>
            </div>

            <ConfirmDialog
                show={isTakeDialogOpen}
                title="¿Quieres tomar este ticket?"
                confirmLabel="Sí, tomar ticket"
                cancelLabel="No, solo mirar"
                processing={isTaking}
                onConfirm={takeTicket}
                onClose={() => setIsTakeDialogOpen(false)}
            >
                {ticket.assignee
                    ? `Ahora lo atiende ${ticket.assignee.name}. Si lo tomas, quedará a tu nombre y podrás escribir en el chat.`
                    : 'Quedará asignado a ti, el solicitante recibirá el saludo de tu equipo y podrás escribir en el chat.'}
            </ConfirmDialog>

            <ConfirmDialog
                show={isCloseDialogOpen}
                title="¿Cerrar el ticket?"
                confirmLabel="Sí, cerrar ticket"
                processing={isChangingStatus}
                onConfirm={changeStatus}
                onClose={() => setIsCloseDialogOpen(false)}
            >
                Cerrar es definitivo: nadie podrá volver a escribir en él ni reabrirlo. Si el problema vuelve, el
                solicitante tendrá que crear un ticket nuevo.
            </ConfirmDialog>
        </AppLayout>
    );
}
