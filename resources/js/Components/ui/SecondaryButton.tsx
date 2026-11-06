import { ButtonHTMLAttributes } from 'react';
import { buttonClasses, ButtonSize } from './buttonClasses';

export default function SecondaryButton({
    className = '',
    size = 'md',
    type = 'button',
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { size?: ButtonSize }) {
    return (
        <button
            {...props}
            type={type}
            className={buttonClasses('secondary', size, className)}
        >
            {children}
        </button>
    );
}
