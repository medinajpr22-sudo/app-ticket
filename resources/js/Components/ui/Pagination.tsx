import { Paginated } from '@/types';
import { Link } from '@inertiajs/react';
import Icon from './Icon';

export default function Pagination({
    meta,
}: {
    meta: Paginated<unknown>['meta'];
}) {
    if (meta.last_page <= 1) {
        return null;
    }

    const [previous, ...rest] = meta.links;
    const next = rest.pop();

    const baseClasses =
        'grid min-w-10 min-h-10 place-items-center rounded-xl border-[1.5px] px-3 text-sm font-extrabold';

    return (
        <nav
            aria-label="Paginación"
            className="mt-5 flex flex-wrap items-center justify-between gap-3"
        >
            <p className="text-sm font-semibold text-muted">
                Mostrando {meta.from}–{meta.to} de {meta.total}
            </p>
            <ul className="flex flex-wrap gap-1.5">
                <li>
                    {previous.url ? (
                        <Link
                            href={previous.url}
                            preserveScroll
                            className={`${baseClasses} border-field bg-white/85 text-wine hover:border-crimson`}
                        >
                            <Icon name="back" size={18} />
                            <span className="sr-only">Página anterior</span>
                        </Link>
                    ) : null}
                </li>
                {rest.map((link) => (
                    <li key={link.label}>
                        {link.url ? (
                            <Link
                                href={link.url}
                                preserveScroll
                                aria-current={link.active ? 'page' : undefined}
                                className={`${baseClasses} ${link.active ? 'border-wine bg-wine text-white' : 'border-field bg-white/85 text-wine hover:border-crimson'}`}
                            >
                                {link.label}
                            </Link>
                        ) : (
                            <span className={`${baseClasses} border-transparent text-muted`}>
                                {link.label}
                            </span>
                        )}
                    </li>
                ))}
                <li>
                    {next?.url ? (
                        <Link
                            href={next.url}
                            preserveScroll
                            className={`${baseClasses} border-field bg-white/85 text-wine hover:border-crimson`}
                        >
                            <Icon name="forward" size={18} />
                            <span className="sr-only">Página siguiente</span>
                        </Link>
                    ) : null}
                </li>
            </ul>
        </nav>
    );
}
