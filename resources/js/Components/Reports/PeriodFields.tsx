import InputError from '@/Components/ui/InputError';
import TextInput from '@/Components/ui/TextInput';
import { ReportPeriod } from '@/types';

/**
 * The first and last day a report covers.
 */
export default function PeriodFields({
    period,
    onChange,
    error,
}: {
    period: ReportPeriod;
    onChange: (period: ReportPeriod) => void;
    error?: string;
}) {
    return (
        <fieldset className="flex flex-wrap items-end gap-3">
            <legend className="sr-only">Periodo</legend>
            <div>
                <label htmlFor="from" className="mb-1 block text-sm font-extrabold">Desde</label>
                <TextInput
                    id="from"
                    type="date"
                    value={period.from}
                    max={period.to}
                    className="w-44"
                    aria-invalid={error ? true : undefined}
                    onChange={(e) => e.target.value && onChange({ ...period, from: e.target.value })}
                />
            </div>
            <div>
                <label htmlFor="to" className="mb-1 block text-sm font-extrabold">Hasta</label>
                <TextInput
                    id="to"
                    type="date"
                    value={period.to}
                    min={period.from}
                    className="w-44"
                    onChange={(e) => e.target.value && onChange({ ...period, to: e.target.value })}
                />
            </div>
            {error && <InputError message={error} className="basis-full" />}
        </fieldset>
    );
}
