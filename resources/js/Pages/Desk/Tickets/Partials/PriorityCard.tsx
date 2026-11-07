import Card from '@/Components/ui/Card';
import InputError from '@/Components/ui/InputError';
import SecondaryButton from '@/Components/ui/SecondaryButton';
import Select from '@/Components/ui/Select';
import TextInput from '@/Components/ui/TextInput';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { Option, Ticket, TicketPriorityValue } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function PriorityCard({ ticket, options }: { ticket: Ticket; options: Option<TicketPriorityValue>[] }) {
    const form = useForm({ priority: ticket.priority.value, reason: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.put(route('desk.tickets.priority.update', ticket.id), {
            ...formSubmitOptions,
            onSuccess: () => form.reset('reason'),
        });
    };

    return (
        <Card aria-labelledby="priority-heading">
            <form onSubmit={submit} noValidate>
                <h2 id="priority-heading" className="mb-3 text-lg font-extrabold">Prioridad</h2>
                <label htmlFor="priority" className="sr-only">Nueva prioridad</label>
                <Select
                    id="priority"
                    value={form.data.priority}
                    onChange={(e) => form.setData('priority', e.target.value as TicketPriorityValue)}
                >
                    {options.map((option) => (
                        <option key={option.value} value={option.value}>{option.label}</option>
                    ))}
                </Select>
                <InputError message={form.errors.priority} />

                {form.data.priority !== ticket.priority.value && (
                    <>
                        <p className="mt-2 text-sm font-semibold text-muted">
                            Los vencimientos se recalculan con los tiempos de la nueva prioridad.
                        </p>
                        <label htmlFor="priority-reason" className="mt-3 mb-1.5 block text-sm font-extrabold">
                            Motivo <span className="font-semibold text-muted">(opcional)</span>
                        </label>
                        <TextInput
                            id="priority-reason"
                            value={form.data.reason}
                            placeholder="Afecta a pacientes"
                            onChange={(e) => form.setData('reason', e.target.value)}
                        />
                        <InputError message={form.errors.reason} />
                        <SecondaryButton type="submit" size="sm" className="mt-3 w-full" disabled={form.processing}>
                            Cambiar prioridad
                        </SecondaryButton>
                    </>
                )}
            </form>
        </Card>
    );
}
