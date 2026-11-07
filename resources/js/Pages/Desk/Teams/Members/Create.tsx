import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import FormField from '@/Components/ui/FormField';
import Icon from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import Select from '@/Components/ui/Select';
import TextInput from '@/Components/ui/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { Department, Location } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Create({
    team,
    role,
    departments,
    locations,
}: {
    team: { id: number; name: string };
    role: { label: string; description: string };
    departments: Department[];
    locations: Location[];
}) {
    const form = useForm({
        name: '',
        email: '',
        job_title: '',
        phone: '',
        department_id: '' as number | '',
        location_id: '' as number | '',
        password: '',
    });
    const { data, setData, errors, processing } = form;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('desk.teams.members.store', team.id), formSubmitOptions);
    };

    return (
        <AppLayout title="Nuevo auxiliar">
            <ButtonLink href={route('desk.teams.show', team.id)} variant="secondary" size="sm" className="mb-4">
                <Icon name="back" size={16} />
                Mi equipo
            </ButtonLink>

            <PageHeader
                title={`Nuevo auxiliar de ${team.name}`}
                description="Crea la cuenta de la persona y queda en tu equipo, lista para atender tickets."
            />

            <form onSubmit={submit} noValidate className="flex max-w-5xl flex-col gap-5">
                <Card aria-labelledby="assignment-heading">
                    <h2 id="assignment-heading" className="mb-3 text-xl font-extrabold">Rol y equipo</h2>
                    <dl className="grid gap-4 md:grid-cols-2">
                        <div className="rounded-2xl border border-line bg-white/70 px-4 py-3">
                            <dt className="text-sm font-bold text-muted">Rol</dt>
                            <dd className="font-extrabold">{role.label}</dd>
                            <dd className="text-sm text-muted">{role.description}</dd>
                        </div>
                        <div className="rounded-2xl border border-line bg-white/70 px-4 py-3">
                            <dt className="text-sm font-bold text-muted">Equipo</dt>
                            <dd className="font-extrabold">{team.name} · auxiliar</dd>
                            <dd className="text-sm text-muted">
                                Atiende los tickets del equipo. Nombrar otro líder lo hace el super administrador.
                            </dd>
                        </div>
                    </dl>
                </Card>

                <Card aria-labelledby="personal-heading">
                    <h2 id="personal-heading" className="mb-4 text-xl font-extrabold">Datos de la persona</h2>
                    <div className="grid gap-4 md:grid-cols-2">
                        <FormField id="name" label="Nombre completo" error={errors.name}>
                            <TextInput
                                id="name"
                                value={data.name}
                                autoComplete="off"
                                isFocused
                                aria-invalid={errors.name ? true : undefined}
                                onChange={(e) => setData('name', e.target.value)}
                            />
                        </FormField>
                        <FormField id="email" label="Correo corporativo" error={errors.email}>
                            <TextInput
                                id="email"
                                type="email"
                                value={data.email}
                                autoComplete="off"
                                placeholder="nombre@empresa.com"
                                aria-invalid={errors.email ? true : undefined}
                                onChange={(e) => setData('email', e.target.value)}
                            />
                        </FormField>
                        <FormField id="job_title" label="Cargo" optional error={errors.job_title}>
                            <TextInput
                                id="job_title"
                                value={data.job_title}
                                placeholder={`Auxiliar de ${team.name}`}
                                aria-invalid={errors.job_title ? true : undefined}
                                onChange={(e) => setData('job_title', e.target.value)}
                            />
                        </FormField>
                        <FormField id="phone" label="Teléfono o extensión" optional error={errors.phone}>
                            <TextInput
                                id="phone"
                                type="tel"
                                value={data.phone}
                                aria-invalid={errors.phone ? true : undefined}
                                onChange={(e) => setData('phone', e.target.value)}
                            />
                        </FormField>
                        <FormField id="department_id" label="Área" optional error={errors.department_id}>
                            <Select
                                id="department_id"
                                value={data.department_id}
                                aria-invalid={errors.department_id ? true : undefined}
                                onChange={(e) => setData('department_id', e.target.value ? Number(e.target.value) : '')}
                            >
                                <option value="">Sin área</option>
                                {departments.map((department) => (
                                    <option key={department.id} value={department.id}>{department.name}</option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField id="location_id" label="Sede" optional error={errors.location_id}>
                            <Select
                                id="location_id"
                                value={data.location_id}
                                aria-invalid={errors.location_id ? true : undefined}
                                onChange={(e) => setData('location_id', e.target.value ? Number(e.target.value) : '')}
                            >
                                <option value="">Sin sede</option>
                                {locations.map((location) => (
                                    <option key={location.id} value={location.id}>{location.name}</option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField
                            id="password"
                            label="Contraseña inicial"
                            optional
                            error={errors.password}
                            hint="Compártela por un canal seguro. Si la dejas vacía, la persona entra con «¿Olvidaste tu contraseña?»."
                        >
                            <TextInput
                                id="password"
                                type="password"
                                value={data.password}
                                autoComplete="new-password"
                                aria-describedby="password-hint"
                                aria-invalid={errors.password ? true : undefined}
                                onChange={(e) => setData('password', e.target.value)}
                            />
                        </FormField>
                    </div>
                </Card>

                <div className="flex justify-end gap-3">
                    <ButtonLink href={route('desk.teams.show', team.id)} variant="secondary">Cancelar</ButtonLink>
                    <PrimaryButton disabled={processing}>Crear auxiliar</PrimaryButton>
                </div>
            </form>
        </AppLayout>
    );
}
