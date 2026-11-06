import { SelectHTMLAttributes } from 'react';
import { fieldClasses } from './fieldClasses';

export default function Select({
    className = '',
    children,
    ...props
}: SelectHTMLAttributes<HTMLSelectElement>) {
    return (
        <select {...props} className={`${fieldClasses} pe-10 ${className}`}>
            {children}
        </select>
    );
}
