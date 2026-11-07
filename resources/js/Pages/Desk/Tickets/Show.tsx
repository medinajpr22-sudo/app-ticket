import AttachmentInput from '@/Components/Ticketing/AttachmentInput';
import Conversation from '@/Components/Ticketing/Conversation';
import PriorityBadge from '@/Components/Ticketing/PriorityBadge';
import SlaBadge from '@/Components/Ticketing/SlaBadge';
import StatusBadge from '@/Components/Ticketing/StatusBadge';
import TicketRatingCard from '@/Components/Ticketing/TicketRatingCard';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import Checkbox from '@/Components/ui/Checkbox';
import Icon from '@/Components/ui/Icon';
import InputError from '@/Components/ui/InputError';
import PageHeader from '@/Components/ui/PageHeader';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import SecondaryButton from '@/Components/ui/SecondaryButton';
import Select from '@/Components/ui/Select';
import TextArea from '@/Components/ui/TextArea';
import AppLayout from '@/Layouts/AppLayout';
import { formatRelative } from '@/lib/formatDate';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { AttachmentLimits, Option, PageProps, Ticket, TicketPriorityValue, TicketStatusValue } from '@/types';
import { router, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import AssignmentCard from './Partials/AssignmentCard';
import PriorityCard from './Partials/PriorityCard';
import TransferCard from './Partials/TransferCard';

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
        addInternalNote: boolean;
        changeStatus: boolean;
        changePriority: boolean;
        assign: boolean;
        transfer: boolean;
    };
}) {
    const { auth, errors } = usePage<PageProps & { errors: Record<string, string> }>().props;
    const message = useForm({ body: '', is_internal: !can.respond, attachments: [] as File[] });
    const [status, setStatus] = useState<TicketStatusValue>(ticket.status.value);
    const canWrite = can.respond || can.addInternalNote;

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

                    {canWrite && (
                        <form onSubmit={sendMessage} noValidate className="mt-6 border-t border-line pt-5">
                            {message.data.is_internal ? null : cannedResponses.length > 0 && (
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
                                {message.data.is_internal ? 'Nota interna (solo la ve TI)' : 'Respuesta al solicitante'}
                            </label>
                            <TextArea
                                id="body"
                                rows={4}
                                value={message.data.body}
                                aria-invalid={message.errors.body ? true : undefined}
                                className={message.data.is_internal ? 'border-dashed border-[#b88a1c] bg-[#fff7e0]' : ''}
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

                            <div className="mt-3 flex flex-wrap items-center gap-3">
                                {can.respond && can.addInternalNote && (
                                    <label className="flex items-center gap-2.5 font-bold">
                                        <Checkbox
                                            checked={message.data.is_internal}
                                            onChange={(e) => message.setData('is_internal', e.target.checked)}
                                        />
                                        Nota interna
                                    </label>
                                )}
                                <PrimaryButton className="ms-auto" disabled={message.processing}>
                                    {message.data.is_internal ? 'Guardar nota' : 'Enviar respuesta'}
                                </PrimaryButton>
                            </div>
                        </form>
                    )}
                </Card>

                <div className="flex flex-col gap-5">
                    {can.take && (
                        <Card className="border-l-[6px] border-l-crimson">
                            <h2 className="text-lg font-extrabold">Nadie lo ha tomado</h2>
                            <p className="mb-3 text-sm text-muted">
                                Al tomarlo, el solicitante recibe el saludo de tu equipo con tu nombre.
                            </p>
                            <PrimaryButton
                                type="button"
                                className="w-full"
                                onClick={() => router.post(route('desk.tickets.take', ticket.id), {}, { preserveScroll: true })}
                            >
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
                            <InputError message={errors.status} />
                            <SecondaryButton
                                size="sm"
                                className="mt-3 w-full"
                                disabled={status === ticket.status.value}
                                onClick={() => router.put(route('desk.tickets.status.update', ticket.id), { status }, { preserveScroll: true })}
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
        </AppLayout>
    );
}
