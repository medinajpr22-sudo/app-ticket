import { classNames } from '@/lib/classNames';
import InputError from './InputError';

export interface RadioCardOption<TValue extends string | number> {
    value: TValue;
    label: string;
    description?: string;
}

/**
 * A group of large selectable cards, used where each choice needs an explanation.
 */
export default function RadioCards<TValue extends string | number>({
    name,
    legend,
    options,
    value,
    onChange,
    error,
    className = 'md:grid-cols-2',
}: {
    name: string;
    legend: string;
    options: RadioCardOption<TValue>[];
    value: TValue | '';
    onChange: (value: TValue) => void;
    error?: string;
    className?: string;
}) {
    return (
        <fieldset>
            <legend className="mb-2 text-sm font-extrabold">{legend}</legend>
            <div className={`grid gap-3 ${className}`}>
                {options.map((option) => {
                    const isSelected = value === option.value;

                    return (
                        <label
                            key={option.value}
                            className={classNames(
                                'flex cursor-pointer flex-col gap-1 rounded-2xl border-[1.5px] p-4 transition has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus',
                                isSelected
                                    ? 'border-wine bg-wine text-white'
                                    : 'border-field bg-white/85 hover:border-crimson',
                            )}
                        >
                            <input
                                type="radio"
                                name={name}
                                value={option.value}
                                checked={isSelected}
                                onChange={() => onChange(option.value)}
                                className="sr-only"
                            />
                            <span className="font-extrabold">{option.label}</span>
                            {option.description && (
                                <span
                                    className={classNames(
                                        'text-sm font-semibold',
                                        isSelected ? 'text-blush' : 'text-muted',
                                    )}
                                >
                                    {option.description}
                                </span>
                            )}
                        </label>
                    );
                })}
            </div>
            <InputError message={error} />
        </fieldset>
    );
}
