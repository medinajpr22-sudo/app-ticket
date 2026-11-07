import { classNames } from '@/lib/classNames';
import { TicketStatusValue } from '@/types';

const steps = ['Recibido', 'En proceso', 'Tu respuesta', 'Resuelto'];

/**
 * Where the ticket is, in the requester's words.
 */
function currentStep(status: TicketStatusValue): number {
    switch (status) {
        case 'received':
            return 0;
        case 'in_progress':
            return 1;
        case 'waiting_on_requester':
            return 2;
        default:
            return 3;
    }
}

export default function TicketProgress({ status }: { status: TicketStatusValue }) {
    const current = currentStep(status);

    return (
        <ol aria-label="Progreso del ticket" className="flex items-start">
            {steps.map((step, index) => (
                <li key={step} className="flex flex-1 items-start last:flex-none">
                    <div
                        className="flex min-w-20 flex-col items-center gap-1 text-center text-xs font-bold"
                        aria-current={index === current ? 'step' : undefined}
                    >
                        <span
                            aria-hidden="true"
                            className={classNames(
                                'grid size-7 place-items-center rounded-full border-2 text-xs font-extrabold',
                                index <= current ? 'border-crimson bg-crimson text-white' : 'border-field bg-white text-muted',
                                index === current && 'shadow-[0_0_0_4px_rgb(201_32_42/0.22)]',
                            )}
                        >
                            {index <= current ? '✓' : index + 1}
                        </span>
                        <span className={index <= current ? 'text-ink' : 'text-muted'}>{step}</span>
                        <span className="sr-only">
                            {index < current ? '(completado)' : index === current ? '(paso actual)' : '(pendiente)'}
                        </span>
                    </div>
                    {index < steps.length - 1 && (
                        <span
                            aria-hidden="true"
                            className={classNames(
                                'mt-3.5 h-[3px] flex-1 rounded-full',
                                index < current ? 'bg-crimson' : 'bg-wine/20',
                            )}
                        />
                    )}
                </li>
            ))}
        </ol>
    );
}
