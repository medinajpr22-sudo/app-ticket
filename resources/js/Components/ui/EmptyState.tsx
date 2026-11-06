import { ReactNode } from 'react';
import Icon, { IconName } from './Icon';

export default function EmptyState({
    icon,
    title,
    description,
    action,
}: {
    icon: IconName;
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <div className="px-5 py-10 text-center text-muted">
            <Icon name={icon} size={34} className="mx-auto mb-2" />
            <p className="font-extrabold text-ink">{title}</p>
            <p>{description}</p>
            {action && <div className="mt-4 flex justify-center">{action}</div>}
        </div>
    );
}
