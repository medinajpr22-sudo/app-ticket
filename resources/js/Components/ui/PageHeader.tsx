import { ReactNode } from 'react';

export default function PageHeader({
    title,
    description,
    actions,
}: {
    title: string;
    description?: string;
    actions?: ReactNode;
}) {
    return (
        <div className="mb-6 flex flex-wrap items-end gap-4">
            <div className="min-w-[min(100%,18rem)] flex-1">
                <h1 className="text-3xl leading-tight font-extrabold tracking-tight">
                    {title}
                </h1>
                {description && (
                    <p className="mt-1 max-w-3xl text-muted">{description}</p>
                )}
            </div>
            {actions && <div className="flex flex-wrap gap-3">{actions}</div>}
        </div>
    );
}
