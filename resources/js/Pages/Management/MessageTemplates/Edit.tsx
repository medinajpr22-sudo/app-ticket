import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { MessageTemplate, MessageTemplatePurposeOption, Team, TemplateVariableOption } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import MessageTemplateForm, { MessageTemplateFormData } from './Partials/MessageTemplateForm';

export default function Edit({
    template,
    purposes,
    teams,
    variables,
}: {
    template: MessageTemplate;
    purposes: MessageTemplatePurposeOption[];
    teams: Team[];
    variables: TemplateVariableOption[];
}) {
    const form = useForm<MessageTemplateFormData>({
        purpose: template.purpose.value,
        team_id: template.team_id ?? '',
        name: template.name,
        body: template.body,
        is_active: template.is_active,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.put(route('management.message-templates.update', template.id), formSubmitOptions);
    };

    return (
        <AppLayout title={`Editar ${template.name}`}>
            <PageHeader title={template.name} description={template.purpose.label} />
            <MessageTemplateForm
                form={form}
                purposes={purposes}
                teams={teams}
                variables={variables}
                isEditing
                onSubmit={submit}
            />
        </AppLayout>
    );
}
