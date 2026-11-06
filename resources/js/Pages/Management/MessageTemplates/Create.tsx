import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import {
    MessageTemplatePurposeOption,
    MessageTemplatePurposeValue,
    Team,
    TemplateVariableOption,
} from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import MessageTemplateForm, { MessageTemplateFormData } from './Partials/MessageTemplateForm';

export default function Create({
    preselectedPurpose,
    preselectedTeamId,
    purposes,
    teams,
    variables,
}: {
    preselectedPurpose: MessageTemplatePurposeValue;
    preselectedTeamId: number | null;
    purposes: MessageTemplatePurposeOption[];
    teams: Team[];
    variables: TemplateVariableOption[];
}) {
    const form = useForm<MessageTemplateFormData>({
        purpose: preselectedPurpose,
        team_id: preselectedTeamId ?? '',
        name: '',
        body: '',
        is_active: true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('management.message-templates.store'), formSubmitOptions);
    };

    return (
        <AppLayout title="Nueva plantilla">
            <PageHeader
                title="Nueva plantilla"
                description="Escribe una vez y reutiliza: el sistema y los agentes completan los datos de cada ticket."
            />
            <MessageTemplateForm
                form={form}
                purposes={purposes}
                teams={teams}
                variables={variables}
                isEditing={false}
                onSubmit={submit}
            />
        </AppLayout>
    );
}
