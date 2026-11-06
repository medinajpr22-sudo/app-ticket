import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import Checkbox from '@/Components/ui/Checkbox';
import FormField from '@/Components/ui/FormField';
import Icon from '@/Components/ui/Icon';
import InputError from '@/Components/ui/InputError';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import RadioCards from '@/Components/ui/RadioCards';
import Select from '@/Components/ui/Select';
import TextArea from '@/Components/ui/TextArea';
import TextInput from '@/Components/ui/TextInput';
import {
    MessageTemplatePurposeOption,
    MessageTemplatePurposeValue,
    Team,
    TemplateVariableOption,
} from '@/types';
import { InertiaFormProps } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

export interface MessageTemplateFormData {
    purpose: MessageTemplatePurposeValue;
    team_id: number | '';
    name: string;
    body: string;
    is_active: boolean;
}

/**
 * Replaces every known {{variable}} with its sample value.
 */
function renderPreview(body: string, variables: TemplateVariableOption[]): string {
    return variables.reduce(
        (text, variable) => text.replace(new RegExp(`\\{\\{\\s*${variable.name.replaceAll('.', '\\.')}\\s*\\}\\}`, 'g'), variable.sample),
        body,
    );
}

export default function MessageTemplateForm({
    form,
    purposes,
    teams,
    variables,
    isEditing,
    onSubmit,
}: {
    form: InertiaFormProps<MessageTemplateFormData>;
    purposes: MessageTemplatePurposeOption[];
    teams: Team[];
    variables: TemplateVariableOption[];
    isEditing: boolean;
    onSubmit: FormEventHandler;
}) {
    const { data, setData, errors, processing } = form;
    const bodyRef = useRef<HTMLTextAreaElement>(null);

    const selectedPurpose = purposes.find((purpose) => purpose.value === data.purpose);
    const availableVariables = variables.filter((variable) => variable.purposes.includes(data.purpose));
    const requesterName = variables.find((variable) => variable.name === 'requester.first_name')?.sample;

    const insertVariable = (token: string) => {
        const textarea = bodyRef.current;
        const start = textarea?.selectionStart ?? data.body.length;
        const end = textarea?.selectionEnd ?? data.body.length;

        setData('body', data.body.slice(0, start) + token + data.body.slice(end));

        requestAnimationFrame(() => {
            textarea?.focus();
            textarea?.setSelectionRange(start + token.length, start + token.length);
        });
    };

    return (
        <form onSubmit={onSubmit} noValidate className="flex max-w-5xl flex-col gap-5">
            <Card aria-labelledby="purpose-heading">
                <h2 id="purpose-heading" className="mb-4 text-xl font-extrabold">
                    ¿Para qué se usa?
                </h2>
                <RadioCards
                    name="purpose"
                    legend="Uso de la plantilla"
                    options={purposes}
                    value={data.purpose}
                    onChange={(purpose) => setData('purpose', purpose)}
                    error={errors.purpose}
                />
            </Card>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
                <Card aria-labelledby="message-heading">
                    <h2 id="message-heading" className="mb-4 text-xl font-extrabold">
                        Mensaje
                    </h2>

                    <div className="grid gap-4 md:grid-cols-2">
                        <FormField id="name" label="Nombre interno" error={errors.name}>
                            <TextInput
                                id="name"
                                value={data.name}
                                placeholder="Saludo de Dialisoft"
                                aria-invalid={errors.name ? true : undefined}
                                onChange={(e) => setData('name', e.target.value)}
                                isFocused={!isEditing}
                            />
                        </FormField>

                        <FormField
                            id="team_id"
                            label="Equipos"
                            error={errors.team_id}
                            hint={
                                selectedPurpose?.is_automatic
                                    ? 'La de un equipo reemplaza a la general para ese equipo.'
                                    : undefined
                            }
                        >
                            <Select
                                id="team_id"
                                value={data.team_id}
                                aria-describedby={selectedPurpose?.is_automatic ? 'team_id-hint' : undefined}
                                onChange={(e) => setData('team_id', e.target.value ? Number(e.target.value) : '')}
                            >
                                <option value="">Todos los equipos</option>
                                {teams.map((team) => (
                                    <option key={team.id} value={team.id}>
                                        {team.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                    </div>

                    <div className="mt-4">
                        <label htmlFor="body" className="mb-1.5 block text-sm font-extrabold">
                            Texto del mensaje
                        </label>
                        <div role="group" aria-label="Insertar variable" className="mb-2 flex flex-wrap gap-1.5">
                            {availableVariables.map((variable) => (
                                <button
                                    key={variable.name}
                                    type="button"
                                    title={variable.token}
                                    onClick={() => insertVariable(variable.token)}
                                    className="inline-flex min-h-9 items-center gap-1 rounded-full border-[1.5px] border-field bg-white/85 px-3 text-sm font-bold text-wine hover:border-crimson"
                                >
                                    <Icon name="plus" size={14} />
                                    {variable.label}
                                </button>
                            ))}
                        </div>
                        <TextArea
                            id="body"
                            ref={bodyRef}
                            rows={6}
                            value={data.body}
                            aria-describedby="body-hint"
                            aria-invalid={errors.body ? true : undefined}
                            onChange={(e) => setData('body', e.target.value)}
                        />
                        <p id="body-hint" className="mt-1.5 text-sm font-semibold text-muted">
                            Los botones insertan variables como <code className="font-bold">{'{{requester.first_name}}'}</code>, que se
                            reemplazan con los datos de cada ticket.
                        </p>
                        <InputError message={errors.body} />
                    </div>

                    <div className="mt-4">
                        <label className="flex min-h-12 items-center gap-3 font-bold">
                            <Checkbox
                                checked={data.is_active}
                                aria-describedby="is_active-hint"
                                aria-invalid={errors.is_active ? true : undefined}
                                onChange={(e) => setData('is_active', e.target.checked)}
                            />
                            Plantilla activa
                        </label>
                        <p id="is_active-hint" className="text-sm font-semibold text-muted">
                            {selectedPurpose?.is_automatic
                                ? 'Solo puede haber una activa de este tipo para los mismos equipos.'
                                : 'Las inactivas no aparecen al responder un ticket.'}
                        </p>
                        <InputError message={errors.is_active} />
                    </div>
                </Card>

                <Card aria-labelledby="preview-heading" className="lg:sticky lg:top-24">
                    <h2 id="preview-heading" className="text-xl font-extrabold">
                        Vista previa
                    </h2>
                    <p className="mb-4 text-sm text-muted">Así lo vería {requesterName}, con datos de ejemplo.</p>
                    <div className="rounded-[16px_4px_16px_16px] bg-wine px-4 py-3 text-white">
                        <p className="whitespace-pre-line">
                            {data.body.trim() ? renderPreview(data.body, availableVariables) : 'Escribe el mensaje para verlo aquí.'}
                        </p>
                    </div>
                </Card>
            </div>

            <div className="flex flex-wrap justify-end gap-3">
                <ButtonLink href={route('management.message-templates.index')} variant="secondary">
                    Cancelar
                </ButtonLink>
                <PrimaryButton disabled={processing}>{isEditing ? 'Guardar cambios' : 'Crear plantilla'}</PrimaryButton>
            </div>
        </form>
    );
}
