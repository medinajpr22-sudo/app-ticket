import TemplateText from '@/Components/Ticketing/TemplateText';
import Badge from '@/Components/ui/Badge';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import EmptyState from '@/Components/ui/EmptyState';
import Icon from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { MessageTemplate, MessageTemplatePurposeOption } from '@/types';

export default function Index({
    templates,
    purposes,
}: {
    templates: MessageTemplate[];
    purposes: MessageTemplatePurposeOption[];
}) {
    const automaticPurposes = purposes.filter((purpose) => purpose.is_automatic);
    const cannedResponses = templates.filter((template) => !template.is_automatic);

    return (
        <AppLayout title="Plantillas de mensajes">
            <PageHeader
                title="Plantillas de mensajes"
                description="Mensajes que el sistema envía solo y respuestas rápidas para los agentes."
                actions={
                    <ButtonLink href={route('management.message-templates.create', { purpose: 'canned_response' })}>
                        <Icon name="plus" />
                        Nueva respuesta rápida
                    </ButtonLink>
                }
            />

            <section aria-labelledby="automatic-heading" className="mb-10">
                <h2 id="automatic-heading" className="text-xl font-extrabold">
                    Mensajes automáticos
                </h2>
                <p className="mb-4 text-muted">
                    Cada equipo usa su propia versión si la tiene; si no, la general.
                </p>

                <div className="grid grid-cols-[repeat(auto-fill,minmax(min(100%,420px),1fr))] items-start gap-4">
                    {automaticPurposes.map((purpose) => (
                        <Card key={purpose.value} aria-labelledby={`purpose-${purpose.value}`}>
                            <h3 id={`purpose-${purpose.value}`} className="text-lg font-extrabold">
                                {purpose.label}
                            </h3>
                            <p className="mb-3 text-sm text-muted">{purpose.description}</p>

                            <ul className="flex flex-col gap-3">
                                {templates
                                    .filter((template) => template.purpose.value === purpose.value)
                                    .map((template) => (
                                        <li key={template.id}>
                                            <TemplateCard template={template} />
                                        </li>
                                    ))}
                            </ul>

                            <div className="mt-4 flex justify-end">
                                <ButtonLink
                                    href={route('management.message-templates.create', { purpose: purpose.value })}
                                    variant="secondary"
                                    size="sm"
                                >
                                    <Icon name="plus" size={16} />
                                    Personalizar para un equipo
                                </ButtonLink>
                            </div>
                        </Card>
                    ))}
                </div>
            </section>

            <section aria-labelledby="canned-heading">
                <h2 id="canned-heading" className="text-xl font-extrabold">
                    Respuestas rápidas
                </h2>
                <p className="mb-4 text-muted">El agente las elige al responder y puede editarlas antes de enviar.</p>

                {cannedResponses.length > 0 ? (
                    <ul className="grid grid-cols-[repeat(auto-fill,minmax(min(100%,420px),1fr))] gap-3">
                        {cannedResponses.map((template) => (
                            <li key={template.id}>
                                <TemplateCard template={template} />
                            </li>
                        ))}
                    </ul>
                ) : (
                    <Card>
                        <EmptyState
                            icon="mail"
                            title="Todavía no hay respuestas rápidas"
                            description="Crea las frases que tu equipo repite a diario."
                        />
                    </Card>
                )}
            </section>
        </AppLayout>
    );
}

function TemplateCard({ template }: { template: MessageTemplate }) {
    return (
        <article className={`rounded-2xl border border-line bg-white/70 p-4 ${template.is_active ? '' : 'opacity-70'}`}>
            <div className="mb-2 flex flex-wrap items-center gap-2">
                <h4 className="me-auto font-extrabold">{template.name}</h4>
                <Badge icon={template.team ? 'users' : 'globe'}>{template.team?.name ?? 'Todos los equipos'}</Badge>
                {!template.is_active && <Badge icon="eyeOff">Inactiva</Badge>}
            </div>
            <TemplateText body={template.body} className="text-sm text-ink" />
            <div className="mt-3 flex items-center gap-3">
                {template.author_name && <span className="text-xs font-semibold text-muted">Por {template.author_name}</span>}
                <ButtonLink
                    href={route('management.message-templates.edit', template.id)}
                    variant="secondary"
                    size="sm"
                    className="ms-auto"
                >
                    <Icon name="pencil" size={16} />
                    Editar
                    <span className="sr-only"> la plantilla {template.name}</span>
                </ButtonLink>
            </div>
        </article>
    );
}
