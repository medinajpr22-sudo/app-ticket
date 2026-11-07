import Card from '@/Components/ui/Card';
import InputError from '@/Components/ui/InputError';
import SecondaryButton from '@/Components/ui/SecondaryButton';
import Select from '@/Components/ui/Select';
import TextInput from '@/Components/ui/TextInput';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { Ticket } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function AssignmentCard({
    ticket,
    members,
}: {
    ticket: Ticket;
    members: Array<{ id: number; name: string }>;
}) {
    const form = useForm({ assignee_id: ticket.assignee?.id ?? ('' as number | ''), reason: '' });
    const hasChanged = form.data.assignee_id !== '' && form.data.assignee_id !== ticket.assignee?.id;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.put(route('desk.tickets.assignment.update', ticket.id), {
            ...formSubmitOptions,
            onSuccess: () => form.reset('reason'),
        });
    };

    return (
        <Card aria-labelledby="assignment-heading">
            <form onSubmit={submit} noValidate>
                <h2 id="assignment-heading" className="mb-3 text-lg font-extrabold">Asignado a</h2>
                <label htmlFor="assignee" className="sr-only">Agente</label>
                <Select
                    id="assignee"
                    value={form.data.assignee_id}
                    onChange={(e) => form.setData('assignee_id', e.target.value ? Number(e.target.value) : '')}
                >
                    {!ticket.assignee && <option value="">Sin asignar</option>}
                    {members.map((member) => (
                        <option key={member.id} value={member.id}>{member.name}</option>
                    ))}
                </Select>
                <InputError message={form.errors.assignee_id} />

                {hasChanged && (
                    <>
                        {!ticket.assignee && (
                            <p className="mt-2 text-sm font-semibold text-muted">
                                Nadie lo había tomado: el solicitante recibirá el saludo en nombre de esa persona.
                            </p>
                        )}
                        <label htmlFor="assignment-reason" className="mt-3 mb-1.5 block text-sm font-extrabold">
                            Motivo <span className="font-semibold text-muted">(opcional)</span>
                        </label>
                        <TextInput
                            id="assignment-reason"
                            value={form.data.reason}
                            placeholder="Conoce mejor este sistema"
                            onChange={(e) => form.setData('reason', e.target.value)}
                        />
                        <InputError message={form.errors.reason} />
                        <SecondaryButton type="submit" size="sm" className="mt-3 w-full" disabled={form.processing}>
                            Reasignar
                        </SecondaryButton>
                    </>
                )}
            </form>
        </Card>
    );
}
