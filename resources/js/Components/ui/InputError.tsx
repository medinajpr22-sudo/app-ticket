import { HTMLAttributes } from 'react';
import Icon from './Icon';

export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p
            {...props}
            data-form-error
            className={`mt-1.5 flex items-center gap-1.5 text-sm font-extrabold text-crimson-dark ${className}`}
        >
            <Icon name="warn" size={16} />
            {message}
        </p>
    ) : null;
}
