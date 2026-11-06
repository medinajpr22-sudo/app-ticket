import { PropsWithChildren } from 'react';
import InputError from './InputError';
import InputLabel from './InputLabel';

/**
 * Label, control, hint and validation error, laid out the same way everywhere.
 * The control must use `id={id}` and `aria-describedby={`${id}-hint`}` when a hint exists.
 */
export default function FormField({
    id,
    label,
    hint,
    error,
    optional = false,
    className = '',
    children,
}: PropsWithChildren<{
    id: string;
    label: string;
    hint?: string;
    error?: string;
    optional?: boolean;
    className?: string;
}>) {
    return (
        <div className={className}>
            <InputLabel htmlFor={id} value={label} optional={optional} />
            {children}
            {hint && (
                <p id={`${id}-hint`} className="mt-1.5 text-sm font-semibold text-muted">
                    {hint}
                </p>
            )}
            <InputError message={error} />
        </div>
    );
}
