import { Checkbox } from '@/components/ui/checkbox';
import { FormError } from '@/components/ui/form-error';
import { LabelWithRequired } from '@/components/LabelWithRequired';

interface WeekdaySelectorProps {
    value: number[];
    onChange: (days: number[]) => void;
    error?: string;
    label?: string;
    required?: boolean;
}

const WEEKDAYS = [
    { value: 1, label: 'L', fullName: 'Lunes' },
    { value: 2, label: 'M', fullName: 'Martes' },
    { value: 3, label: 'M', fullName: 'Miércoles' },
    { value: 4, label: 'J', fullName: 'Jueves' },
    { value: 5, label: 'V', fullName: 'Viernes' },
    { value: 6, label: 'S', fullName: 'Sábado' },
    { value: 7, label: 'D', fullName: 'Domingo' },
];

export function WeekdaySelector({ value, onChange, error, label = 'Días de la semana', required = false }: WeekdaySelectorProps) {
    const handleToggle = (day: number) => {
        if (value.includes(day)) {
            onChange(value.filter((d) => d !== day));
        } else {
            onChange([...value, day].sort());
        }
    };

    return (
        <div className="space-y-2">
            <LabelWithRequired required={required}>{label}</LabelWithRequired>
            <div className="flex flex-wrap gap-2">
                {WEEKDAYS.map((day) => (
                    <div key={day.value} className="flex flex-col items-center gap-1">
                        <div
                            className={`flex h-10 w-10 items-center justify-center rounded-md border-2 transition-colors ${
                                value.includes(day.value)
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-input bg-background hover:bg-accent hover:text-accent-foreground'
                            } cursor-pointer`}
                            onClick={() => handleToggle(day.value)}
                            title={day.fullName}
                        >
                            <span className="text-sm font-semibold">{day.label}</span>
                        </div>
                        <Checkbox checked={value.includes(day.value)} onCheckedChange={() => handleToggle(day.value)} className="sr-only" />
                    </div>
                ))}
            </div>
            <FormError message={error} />
            {value.length > 0 && (
                <p className="text-sm text-muted-foreground">
                    Seleccionados: {value.map((d) => WEEKDAYS.find((w) => w.value === d)?.fullName).join(', ')}
                </p>
            )}
        </div>
    );
}
