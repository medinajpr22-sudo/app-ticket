import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { Department, Location, Role, Team, User } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import UserForm, { UserFormData } from './Partials/UserForm';

export default function Edit({
    user,
    roles,
    departments,
    locations,
    teams,
}: {
    user: User;
    roles: Role[];
    departments: Department[];
    locations: Location[];
    teams: Team[];
}) {
    const form = useForm<UserFormData>({
        role_id: user.role?.id ?? '',
        name: user.name,
        email: user.email,
        job_title: user.job_title ?? '',
        phone: user.phone ?? '',
        department_id: user.department?.id ?? '',
        location_id: user.location?.id ?? '',
        is_active: user.is_active,
        password: '',
        team_ids: user.teams?.map((team) => team.id) ?? [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.put(route('management.users.update', user.id), {
            onFinish: () => form.reset('password'),
        });
    };

    return (
        <AppLayout title={`Editar a ${user.name}`}>
            <PageHeader title={user.name} description={user.email} />
            <UserForm
                form={form}
                roles={roles}
                departments={departments}
                locations={locations}
                teams={teams}
                isEditing
                onSubmit={submit}
            />
        </AppLayout>
    );
}
