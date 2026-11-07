import Card from '@/Components/ui/Card';
import Icon from '@/Components/ui/Icon';
import InputError from '@/Components/ui/InputError';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import TextArea from '@/Components/ui/TextArea';
import { classNames } from '@/lib/classNames';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { Ticket } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const SCORES = [1, 2, 3, 4, 5];

const scoreLabels: Record<number, string> = {
    1: 'Muy mala',
    2: 'Mala',
    3: 'Regular',
    4: 'Buena',
    5: 'Excelente',
};

/**
 * Asks the requester to rate the attention, or shows the rating they gave.
 */
export default function TicketRatingCard({
    ticket,
    canRate,
    title = 'Tu calificación',
}: {
    ticket: Ticket;
    canRate: boolean;
    title?: string;
}) {
    const form = useForm({ score: 0, comment: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('portal.tickets.rating.store', ticket.id), formSubmitOptions);
    };

    if (ticket.rating) {
        return (
            <Card aria-labelledby="rating-heading">
                <h2 id="rating-heading" className="text-lg font-extrabold">{title}</h2>
                <Stars score={ticket.rating.score} />
                <p className="mt-1 text-sm font-bold">{scoreLabels[ticket.rating.score]}</p>
                {ticket.rating.comment && <p className="mt-2 text-sm text-muted">“{ticket.rating.comment}”</p>}
            </Card>
        );
    }

    if (!canRate) {
        return null;
    }

    return (
        <Card aria-labelledby="rating-heading">
            <form onSubmit={submit} noValidate>
                <h2 id="rating-heading" className="text-lg font-extrabold">¿Cómo fue la atención?</h2>
                <p className="mb-3 text-sm text-muted">Tu opinión nos ayuda a mejorar.</p>

                <fieldset>
                    <legend className="sr-only">Calificación de 1 a 5 estrellas</legend>
                    <div className="flex gap-1">
                        {SCORES.map((score) => (
                            <label
                                key={score}
                                title={scoreLabels[score]}
                                className={classNames(
                                    'grid size-11 cursor-pointer place-items-center rounded-xl transition has-focus-visible:outline-3 has-focus-visible:outline-focus',
                                    score <= form.data.score ? 'text-[#b27a00]' : 'text-[#a99b9b] hover:text-[#b27a00]',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="score"
                                    value={score}
                                    checked={form.data.score === score}
                                    onChange={() => form.setData('score', score)}
                                    className="sr-only"
                                />
                                <Icon name="star" size={30} className={score <= form.data.score ? 'fill-current' : ''} />
                                <span className="sr-only">
                                    {score} de 5: {scoreLabels[score]}
                                </span>
                            </label>
                        ))}
                    </div>
                    <InputError message={form.errors.score} />
                </fieldset>

                {form.data.score > 0 && (
                    <>
                        <label htmlFor="rating-comment" className="mt-3 mb-1.5 block text-sm font-extrabold">
                            Comentario <span className="font-semibold text-muted">(opcional)</span>
                        </label>
                        <TextArea
                            id="rating-comment"
                            rows={2}
                            value={form.data.comment}
                            onChange={(e) => form.setData('comment', e.target.value)}
                        />
                        <InputError message={form.errors.comment} />
                        <PrimaryButton size="sm" className="mt-3 w-full" disabled={form.processing}>
                            Enviar calificación
                        </PrimaryButton>
                    </>
                )}
            </form>
        </Card>
    );
}

function Stars({ score }: { score: number }) {
    return (
        <p className="mt-1 flex gap-0.5 text-[#b27a00]" aria-label={`${score} de 5 estrellas`}>
            {SCORES.map((value) => (
                <Icon key={value} name="star" size={22} className={value <= score ? 'fill-current' : 'text-[#a99b9b]'} />
            ))}
        </p>
    );
}
