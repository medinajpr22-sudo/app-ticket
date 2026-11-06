import { PropsWithChildren } from 'react';
import Icon, { IconName } from './Icon';

export type BadgeTone = 'neutral' | 'success' | 'warning' | 'info' | 'brand';

const tones: Record<BadgeTone, string> = {
    neutral: 'text-[#4b4b4b]',
    success: 'text-success',
    warning: 'text-warning',
    info: 'text-info',
    brand: 'text-crimson-dark',
};

export default function Badge({
    tone = 'neutral',
    icon,
    className = '',
    children,
}: PropsWithChildren<{ tone?: BadgeTone; icon?: IconName; className?: string }>) {
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full border border-current bg-white/80 px-2.5 py-0.5 text-xs font-extrabold whitespace-nowrap ${tones[tone]} ${className}`}
        >
            {icon && <Icon name={icon} size={14} />}
            {children}
        </span>
    );
}
