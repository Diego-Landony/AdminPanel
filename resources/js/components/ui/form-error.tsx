import { cn } from '@/lib/utils';
import { AlertCircle } from 'lucide-react';

interface FormErrorProps {
    message?: string | null;
    className?: string;
    showIcon?: boolean;
}

export function FormError({ message, className, showIcon = true }: FormErrorProps) {
    if (!message) {
        return null;
    }

    return (
        <div
            className={cn(
                'flex items-center gap-2 text-sm text-destructive',
                className
            )}
            role="alert"
        >
            {showIcon && <AlertCircle className="h-4 w-4 flex-shrink-0" />}
            <span>{message}</span>
        </div>
    );
}
