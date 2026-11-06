import { classNames } from '@/lib/classNames';
import { Option } from '@/types';
import Icon, { IconName } from './Icon';
import InputError from './InputError';

export default function IconPicker({
    icons,
    value,
    onChange,
    error,
}: {
    icons: Option<IconName>[];
    value: IconName;
    onChange: (icon: IconName) => void;
    error?: string;
}) {
    return (
        <fieldset>
            <legend className="mb-2 text-sm font-extrabold">Icono</legend>
            <div className="flex flex-wrap gap-2">
                {icons.map((icon) => (
                    <label
                        key={icon.value}
                        title={icon.label}
                        className={classNames(
                            'grid size-12 cursor-pointer place-items-center rounded-[14px] border-[1.5px] transition has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus',
                            value === icon.value
                                ? 'border-wine bg-wine text-white'
                                : 'border-field bg-white/85 text-crimson-dark hover:border-crimson',
                        )}
                    >
                        <input
                            type="radio"
                            name="icon"
                            value={icon.value}
                            checked={value === icon.value}
                            onChange={() => onChange(icon.value)}
                            className="sr-only"
                        />
                        <Icon name={icon.value} size={22} />
                        <span className="sr-only">{icon.label}</span>
                    </label>
                ))}
            </div>
            <InputError message={error} />
        </fieldset>
    );
}
