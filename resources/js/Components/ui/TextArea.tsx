import { ComponentProps } from 'react';
import { fieldClasses } from './fieldClasses';

export default function TextArea({ className = '', rows = 4, ...props }: ComponentProps<'textarea'>) {
    return <textarea {...props} rows={rows} className={fieldClasses(`resize-y ${className}`)} />;
}
