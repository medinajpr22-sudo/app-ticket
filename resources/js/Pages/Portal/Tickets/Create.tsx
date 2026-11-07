import Card from '@/Components/ui/Card';
import FormField from '@/Components/ui/FormField';
import Icon, { IconName } from '@/Components/ui/Icon';
import InputError from '@/Components/ui/InputError';
import PageHeader from '@/Components/ui/PageHeader';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import RadioCards from '@/Components/ui/RadioCards';
import SecondaryButton from '@/Components/ui/SecondaryButton';
import TextArea from '@/Components/ui/TextArea';
import TextInput from '@/Components/ui/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { classNames } from '@/lib/classNames';
import { formatDuration } from '@/lib/formatDuration';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { TeamTypeValue, TicketPriorityValue } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface CatalogCategory {
    id: number;
    name: string;
    description: string | null;
    icon: IconName;
    team: { id: number; name: string; type: TeamTypeValue };
    first_response_minutes: Partial<Record<TicketPriorityValue, number>>;
}

const urgencyLabels: Partial<Record<TicketPriorityValue, string>> = {
    low: 'Puedo esperar',
    medium: 'Me frena un poco',
    high: 'No puedo trabajar',
};

export default function Create({
    categories,
    priorities,
}: {
    categories: CatalogCategory[];
    priorities: TicketPriorityValue[];
}) {
    const form = useForm({
        category_id: '' as number | '',
        subject: '',
        description: '',
        priority: 'medium' as TicketPriorityValue,
    });
    const { data, setData, errors, processing } = form;

    const selectedCategory = categories.find((category) => category.id === data.category_id);
    const generalSupport = categories.filter((category) => category.team.type === 'general_support');
    const applicationTeams = [...new Map(
        categories
            .filter((category) => category.team.type === 'application_support')
            .map((category) => [category.team.id, category.team]),
    ).values()];

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('portal.tickets.store'), formSubmitOptions);
    };

    const categoryTile = (category: CatalogCategory) => (
        <li key={category.id} className="flex">
            <button
                type="button"
                onClick={() => setData('category_id', category.id)}
                className="glass flex w-full flex-col items-start gap-2 p-4 text-left font-extrabold transition hover:-translate-y-0.5 hover:border-crimson"
            >
                <span className="grid size-11 place-items-center rounded-[14px] border border-crimson/20 bg-crimson/10 text-crimson-dark">
                    <Icon name={category.icon} size={22} />
                </span>
                {category.name}
                {category.description && <span className="text-sm font-semibold text-muted">{category.description}</span>}
            </button>
        </li>
    );

    return (
        <AppLayout title="Nuevo ticket">
            <PageHeader
                title="Nuevo ticket"
                description="Elige qué necesitas y cuéntanos qué pasa. Llegará directo al equipo que lo resuelve."
            />

            <ol aria-label="Pasos" className="mb-6 flex max-w-3xl gap-3">
                {['Categoría', 'Detalles'].map((step, index) => {
                    const isCurrent = (selectedCategory ? 1 : 0) === index;

                    return (
                        <li
                            key={step}
                            aria-current={isCurrent ? 'step' : undefined}
                            className={classNames(
                                'flex-1 border-t-[5px] pt-1.5 text-sm font-extrabold',
                                index <= (selectedCategory ? 1 : 0) ? 'border-crimson text-ink' : 'border-wine/20 text-muted',
                            )}
                        >
                            {index + 1}. {step}
                        </li>
                    );
                })}
            </ol>

            {!selectedCategory && (
                <div className="flex flex-col gap-8">
                    <section aria-labelledby="general-heading">
                        <h2 id="general-heading" className="text-xl font-extrabold">Soporte general de TI</h2>
                        <p className="mb-4 text-muted">Equipos, accesos, correo, redes y más.</p>
                        <ul className="grid grid-cols-[repeat(auto-fill,minmax(180px,1fr))] gap-3">
                            {generalSupport.map(categoryTile)}
                        </ul>
                    </section>

                    {applicationTeams.map((team) => (
                        <section key={team.id} aria-labelledby={`team-${team.id}`}>
                            <h2 id={`team-${team.id}`} className="mb-4 text-xl font-extrabold">
                                Consulta directa a {team.name}
                            </h2>
                            <ul className="grid grid-cols-[repeat(auto-fill,minmax(180px,1fr))] gap-3">
                                {categories.filter((category) => category.team.id === team.id).map(categoryTile)}
                            </ul>
                        </section>
                    ))}
                    <InputError message={errors.category_id} />
                </div>
            )}

            {selectedCategory && (
                <form onSubmit={submit} noValidate className="flex max-w-3xl flex-col gap-5">
                    <Card>
                        <div className="mb-4 flex flex-wrap items-center gap-3">
                            <span className="grid size-11 place-items-center rounded-[14px] border border-crimson/20 bg-crimson/10 text-crimson-dark">
                                <Icon name={selectedCategory.icon} size={22} />
                            </span>
                            <div className="flex-1">
                                <h2 className="text-xl font-extrabold">{selectedCategory.name}</h2>
                                <p className="text-sm text-muted">Lo atiende el equipo {selectedCategory.team.name}</p>
                            </div>
                            <SecondaryButton size="sm" onClick={() => setData('category_id', '')}>
                                <Icon name="back" size={16} />
                                Cambiar
                            </SecondaryButton>
                        </div>

                        <div className="flex flex-col gap-4">
                            <FormField id="subject" label="Asunto" error={errors.subject}>
                                <TextInput
                                    id="subject"
                                    value={data.subject}
                                    placeholder="No puedo conectarme a la VPN"
                                    aria-invalid={errors.subject ? true : undefined}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    isFocused
                                />
                            </FormField>

                            <FormField
                                id="description"
                                label="Descripción"
                                error={errors.description}
                                hint="¿Qué intentabas hacer? ¿Qué mensaje aparece? ¿Desde cuándo pasa?"
                            >
                                <TextArea
                                    id="description"
                                    rows={5}
                                    value={data.description}
                                    aria-describedby="description-hint"
                                    aria-invalid={errors.description ? true : undefined}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                            </FormField>

                            <RadioCards
                                name="priority"
                                legend="¿Qué tan urgente es?"
                                className="sm:grid-cols-3"
                                options={priorities.map((priority) => {
                                    const minutes = selectedCategory.first_response_minutes[priority];

                                    return {
                                        value: priority,
                                        label: urgencyLabels[priority] ?? priority,
                                        description: minutes ? `Respuesta en ${formatDuration(minutes)} laborales` : undefined,
                                    };
                                })}
                                value={data.priority}
                                onChange={(priority) => setData('priority', priority)}
                                error={errors.priority}
                            />
                        </div>
                    </Card>

                    <div className="flex justify-end">
                        <PrimaryButton disabled={processing}>
                            <Icon name="check" />
                            Enviar ticket
                        </PrimaryButton>
                    </div>
                </form>
            )}
        </AppLayout>
    );
}
