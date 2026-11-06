import TextInput from './TextInput';

/**
 * Edits a number of minutes as separate hours and minutes fields.
 */
export default function DurationInput({
    id,
    label,
    minutes,
    onChange,
    isInvalid = false,
}: {
    id: string;
    label: string;
    minutes: number;
    onChange: (minutes: number) => void;
    isInvalid?: boolean;
}) {
    const hourPart = Math.floor(minutes / 60);
    const minutePart = minutes % 60;

    const toNumber = (value: string) => Math.max(0, Number.parseInt(value, 10) || 0);

    return (
        <div className="flex items-center gap-2">
            <TextInput
                id={id}
                type="number"
                min={0}
                max={8760}
                value={hourPart}
                aria-label={`${label}, horas`}
                aria-invalid={isInvalid || undefined}
                className="w-20 text-right"
                onChange={(e) => onChange(toNumber(e.target.value) * 60 + minutePart)}
            />
            <span aria-hidden="true" className="font-bold text-muted">h</span>
            <TextInput
                type="number"
                min={0}
                max={59}
                step={5}
                value={minutePart}
                aria-label={`${label}, minutos`}
                aria-invalid={isInvalid || undefined}
                className="w-20 text-right"
                onChange={(e) => onChange(hourPart * 60 + Math.min(59, toNumber(e.target.value)))}
            />
            <span aria-hidden="true" className="font-bold text-muted">min</span>
        </div>
    );
}
