import { HTMLAttributes } from 'react';

export default function Card({
    className = '',
    children,
    ...props
}: HTMLAttributes<HTMLElement>) {
    return (
        <section {...props} className={`glass p-6 ${className}`}>
            {children}
        </section>
    );
}
