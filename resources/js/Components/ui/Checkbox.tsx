import { InputHTMLAttributes } from 'react';

export default function Checkbox({
    className = '',
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            {...props}
            type="checkbox"
            className={`size-5 rounded-md border-[1.5px] border-field text-crimson focus:ring-crimson ${className}`}
        />
    );
}
