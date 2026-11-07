import Conversation from '@/Components/Ticketing/Conversation';
import StatusBadge from '@/Components/Ticketing/StatusBadge';
import TicketProgress from '@/Components/Ticketing/TicketProgress';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import FormField from '@/Components/ui/FormField';
import Icon from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import SecondaryButton from '@/Components/ui/SecondaryButton';
import TextArea from '@/Components/ui/TextArea';
import AppLayout from '@/Layouts/AppLayout';
import { formatRelative } from '@/lib/formatDate';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { PageProps, Ticket } from '@/types';
import { router, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

/**
 * What happens next, in plain words for the requester.
 */
function nextStep(ticket: Ticket): { title: string; text: string } | null {
    switch (ticket.status.value) {
        case 'new':
            return { title: 'Recibimos tu ticket', text: 'Una persona de TI lo tomará pronto.' };
        case 'in_progress':
        case 'waiting_on_third_party':
            return {
                title: `${ticket.assignee?.name ?? 'Un agente'} está trabajando en tu caso`,
                text: 'Te avisaremos apenas haya novedades.',
            };
        case 'waiting_on_requester':
            return { title: 'Necesitamos tu respuesta para continuar', text: 'Responde abajo y retomamos tu caso.' };
        default:
            return null;
    }
}

export default function Show({
    ticket,
    can,
}: {
    ticket: Ticket;
    can: { reply: boolean; resolve: boolean; reopen: boolean };
}) {
    const { auth } = usePage<PageProps>().props;
    const form = useForm({ body: '' });
    const next = nextStep(ticket);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('portal.tickets.messages.store', ticket.id), {
            ...formSubmitOptions,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <AppLayout title={`Ticket #${ticket.id}`}>
            <ButtonLink href={route('portal.tickets.index')} variant="secondary" size="sm" className="mb-4">
                <Icon name="back" size={16} />
                Mis tickets
            </ButtonLink>

            <PageHeader
                title={`#${ticket.id} · ${ticket.subject}`}
                description={`${ticket.category?.name} · creado ${formatRelative(ticket.created_at)}`}
                actions={<StatusBadge status={ticket.status} />}
            />

            <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
                <Card aria-labelledby="conversation-heading">
                    <h2 className="sr-only">Progreso</h2>
                    <TicketProgress status={ticket.status.value} />

                    {next && (
                        <div role="note" className="mt-5 flex gap-3 rounded-2xl border border-crimson/30 bg-crimson/8 px-4 py-3">
                            <Icon name="clock" className="mt-0.5 text-crimson-dark" />
                            <p>
                                <span className="block font-extrabold">{next.title}</span>
                                <span className="text-muted">{next.text}</span>
                            </p>
                        </div>
                    )}

                    <h2 id="conversation-heading" className="mt-6 mb-4 text-xl font-extrabold">
                        Conversación
                    </h2>
                    <Conversation messages={ticket.messages ?? []} viewerId={auth.user.id} />

                    {can.reply && (
                        <form onSubmit={submit} noValidate className="mt-6 border-t border-line pt-5">
                            <FormField id="body" label="Tu respuesta" error={form.errors.body}>
                                <TextArea
                                    id="body"
                                    rows={3}
                                    value={form.data.body}
                                    aria-invalid={form.errors.body ? true : undefined}
                                    onChange={(e) => form.setData('body', e.target.value)}
                                />
                            </FormField>
                            <div className="mt-3 flex justify-end">
                                <PrimaryButton disabled={form.processing}>Enviar</PrimaryButton>
                            </div>
                        </form>
                    )}
                </Card>

                <div className="flex flex-col gap-5">
                    <Card aria-labelledby="details-heading">
                        <h2 id="details-heading" className="mb-2 text-xl font-extrabold">Detalles</h2>
                        <dl className="divide-y divide-line">
                            {[
                                ['Equipo', ticket.team?.name],
                                ['Te atiende', ticket.assignee?.name ?? 'Sin asignar todavía'],
                                ['Prioridad', ticket.priority.label],
                                ['Tipo', ticket.type.label],
                            ].map(([term, value]) => (
                                <div key={term} className="flex justify-between gap-3 py-2.5">
                                    <dt className="font-bold text-muted">{term}</dt>
                                    <dd className="text-right font-extrabold">{value}</dd>
                                </div>
                            ))}
                        </dl>
                    </Card>

                    {can.resolve && (
                        <Card>
                            <h2 className="text-lg font-extrabold">¿Ya se resolvió?</h2>
                            <p className="mb-3 text-sm text-muted">Si ya no necesitas ayuda, puedes cerrarlo tú.</p>
                            <SecondaryButton
                                size="sm"
                                onClick={() => router.post(route('portal.tickets.resolution.store', ticket.id), {}, { preserveScroll: true })}
                            >
                                <Icon name="check" size={16} />
                                Marcar como resuelto
                            </SecondaryButton>
                        </Card>
                    )}

                    {can.reopen && (
                        <Card>
                            <h2 className="text-lg font-extrabold">¿El problema volvió?</h2>
                            <p className="mb-3 text-sm text-muted">Reábrelo y vuelve a quien lo atendió.</p>
                            <SecondaryButton
                                size="sm"
                                onClick={() => router.delete(route('portal.tickets.resolution.destroy', ticket.id), { preserveScroll: true })}
                            >
                                Reabrir ticket
                            </SecondaryButton>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
