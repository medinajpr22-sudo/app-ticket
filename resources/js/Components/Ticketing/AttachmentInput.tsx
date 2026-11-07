import Icon from '@/Components/ui/Icon';
import InputError from '@/Components/ui/InputError';
import { formatFileSize } from '@/lib/formatFileSize';
import { AttachmentLimits } from '@/types';
import { ChangeEvent, useId, useState } from 'react';

/**
 * Picks files for a message and warns right away about the ones that would
 * be rejected. The server validates them again on submit.
 */
export default function AttachmentInput({
    files,
    onChange,
    limits,
    error,
}: {
    files: File[];
    onChange: (files: File[]) => void;
    limits: AttachmentLimits;
    error?: string;
}) {
    const inputId = useId();
    const [warning, setWarning] = useState<string | null>(null);

    const addFiles = (event: ChangeEvent<HTMLInputElement>) => {
        const picked = Array.from(event.target.files ?? []);
        const problems: string[] = [];

        const accepted = picked.filter((file) => {
            const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

            if (!limits.extensions.includes(extension)) {
                problems.push(`${file.name}: tipo no permitido`);
                return false;
            }

            if (file.size > limits.max_kilobytes * 1024) {
                problems.push(`${file.name}: supera ${formatFileSize(limits.max_kilobytes * 1024)}`);
                return false;
            }

            return true;
        });

        const next = [...files, ...accepted];

        if (next.length > limits.max_files) {
            problems.push(`Máximo ${limits.max_files} archivos por mensaje`);
        }

        setWarning(problems.length > 0 ? problems.join(' · ') : null);
        onChange(next.slice(0, limits.max_files));
        event.target.value = '';
    };

    return (
        <div>
            <input
                id={inputId}
                type="file"
                multiple
                accept={limits.extensions.map((extension) => `.${extension}`).join(',')}
                onChange={addFiles}
                className="sr-only"
            />
            <label
                htmlFor={inputId}
                className="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-xl border-[1.5px] border-dashed border-field bg-white/60 px-3.5 text-sm font-bold text-wine hover:border-crimson has-focus-visible:outline-3 has-focus-visible:outline-focus"
            >
                <Icon name="clip" size={18} />
                Adjuntar archivos
            </label>
            <span className="ms-2 text-xs font-semibold text-muted">
                Hasta {limits.max_files}, de {formatFileSize(limits.max_kilobytes * 1024)} cada uno
            </span>

            {files.length > 0 && (
                <ul className="mt-2 flex flex-wrap gap-2">
                    {files.map((file, index) => (
                        <li
                            key={`${file.name}-${index}`}
                            className="inline-flex items-center gap-2 rounded-full border border-line bg-white/85 py-1 ps-3 pe-1 text-sm font-bold"
                        >
                            <Icon name="clip" size={14} className="text-muted" />
                            {file.name}
                            <span className="font-semibold text-muted">{formatFileSize(file.size)}</span>
                            <button
                                type="button"
                                onClick={() => onChange(files.filter((_, position) => position !== index))}
                                className="grid size-7 place-items-center rounded-full hover:bg-crimson/10"
                            >
                                <Icon name="x" size={14} />
                                <span className="sr-only">Quitar {file.name}</span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {warning && <p className="mt-2 text-sm font-bold text-warning">{warning}</p>}
            <InputError message={error} />
        </div>
    );
}
