import { LabelHTMLAttributes } from 'react';

export default function InputLabel({
    value,
    optional = false,
    className = '',
    children,
    ...props
}: LabelHTMLAttributes<HTMLLabelElement> & {
    value?: string;
    optional?: boolean;
}) {
    return (
        <label
            {...props}
            className={`mb-1.5 block text-sm font-extrabold text-ink ${className}`}
        >
            {value ? value : children}
            {optional && (
                <span className="ms-1 font-semibold text-muted">(opcional)</span>
            )}
        </label>
    );
}
