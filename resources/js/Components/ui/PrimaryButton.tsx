import { ButtonHTMLAttributes } from 'react';
import { buttonClasses, ButtonSize } from './buttonClasses';

export default function PrimaryButton({
    className = '',
    size = 'md',
    type = 'submit',
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { size?: ButtonSize }) {
    return (
        <button
            {...props}
            type={type}
            className={buttonClasses('primary', size, className)}
        >
            {children}
        </button>
    );
}
