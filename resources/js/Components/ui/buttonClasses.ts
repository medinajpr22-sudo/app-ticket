import { classNames } from '@/lib/classNames';

export type ButtonVariant = 'primary' | 'secondary' | 'danger';

export type ButtonSize = 'md' | 'sm';

const variants: Record<ButtonVariant, string> = {
    primary:
        'border-crimson bg-crimson text-white shadow-[0_6px_16px_rgb(201_32_42/0.26)] hover:border-crimson-dark hover:bg-crimson-dark',
    secondary:
        'border-field bg-white/85 text-wine hover:border-crimson hover:text-crimson-dark',
    danger: 'border-wine bg-wine text-white hover:bg-wine-dark',
};

const sizes: Record<ButtonSize, string> = {
    md: 'min-h-[46px] px-5 py-2.5',
    sm: 'min-h-10 px-3.5 py-1.5 text-sm',
};

/**
 * Shared look for buttons and links that act as buttons.
 */
export function buttonClasses(
    variant: ButtonVariant = 'primary',
    size: ButtonSize = 'md',
    className = '',
): string {
    return classNames(
        'inline-flex items-center justify-center gap-2 rounded-xl border-[1.5px] font-extrabold transition active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60',
        variants[variant],
        sizes[size],
        className,
    );
}
