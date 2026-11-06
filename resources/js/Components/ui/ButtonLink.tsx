import { InertiaLinkProps, Link } from '@inertiajs/react';
import { buttonClasses, ButtonSize, ButtonVariant } from './buttonClasses';

export default function ButtonLink({
    variant = 'primary',
    size = 'md',
    className = '',
    children,
    ...props
}: Omit<InertiaLinkProps, 'size'> & {
    variant?: ButtonVariant;
    size?: ButtonSize;
}) {
    return (
        <Link {...props} className={buttonClasses(variant, size, className)}>
            {children}
        </Link>
    );
}
