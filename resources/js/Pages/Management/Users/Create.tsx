import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { Department, Location, Role, Team } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import UserForm, { UserFormData } from './Partials/UserForm';

export default function Create({
    roles,
    departments,
    locations,
    teams,
}: {
    roles: Role[];
    departments: Department[];
    locations: Location[];
    teams: Team[];
}) {
    const form = useForm<UserFormData>({
        role_id: roles.find((role) => role.slug === 'user')?.id ?? '',
        name: '',
        email: '',
        job_title: '',
        phone: '',
        department_id: '',
        location_id: '',
        is_active: true,
        password: '',
        team_ids: [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('management.users.store'), formSubmitOptions);
    };

    return (
        <AppLayout title="Nuevo usuario">
            <PageHeader
                title="Nuevo usuario"
                description="Crea la cuenta y define qué puede hacer la persona en la mesa de ayuda."
            />
            <UserForm
                form={form}
                roles={roles}
                departments={departments}
                locations={locations}
                teams={teams}
                isEditing={false}
                onSubmit={submit}
            />
        </AppLayout>
    );
}
