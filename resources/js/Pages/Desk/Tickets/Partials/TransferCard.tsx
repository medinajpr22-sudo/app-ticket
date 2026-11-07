import Card from '@/Components/ui/Card';
import Icon from '@/Components/ui/Icon';
import InputError from '@/Components/ui/InputError';
import SecondaryButton from '@/Components/ui/SecondaryButton';
import Select from '@/Components/ui/Select';
import TextInput from '@/Components/ui/TextInput';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { Ticket } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function TransferCard({ ticket, teams }: { ticket: Ticket; teams: Array<{ id: number; name: string }> }) {
    const form = useForm({ team_id: '' as number | '', reason: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('desk.tickets.transfer', ticket.id), formSubmitOptions);
    };

    return (
        <Card aria-labelledby="transfer-heading">
            <form onSubmit={submit} noValidate>
                <h2 id="transfer-heading" className="text-lg font-extrabold">Transferir a otro equipo</h2>
                <p className="mb-3 text-sm text-muted">
                    Vuelve a "Recibido" para que el otro equipo lo tome. Los vencimientos no cambian.
                </p>
                <label htmlFor="transfer-team" className="sr-only">Equipo de destino</label>
                <Select
                    id="transfer-team"
                    value={form.data.team_id}
                    aria-invalid={form.errors.team_id ? true : undefined}
                    onChange={(e) => form.setData('team_id', e.target.value ? Number(e.target.value) : '')}
                >
                    <option value="">Elige un equipo</option>
                    {teams.map((team) => (
                        <option key={team.id} value={team.id}>{team.name}</option>
                    ))}
                </Select>
                <InputError message={form.errors.team_id} />

                {form.data.team_id !== '' && (
                    <>
                        <label htmlFor="transfer-reason" className="mt-3 mb-1.5 block text-sm font-extrabold">Motivo</label>
                        <TextInput
                            id="transfer-reason"
                            value={form.data.reason}
                            placeholder="Es un problema de red, no del equipo"
                            aria-invalid={form.errors.reason ? true : undefined}
                            onChange={(e) => form.setData('reason', e.target.value)}
                        />
                        <InputError message={form.errors.reason} />
                        <SecondaryButton type="submit" size="sm" className="mt-3 w-full" disabled={form.processing}>
                            <Icon name="transfer" size={16} />
                            Transferir
                        </SecondaryButton>
                    </>
                )}
            </form>
        </Card>
    );
}
