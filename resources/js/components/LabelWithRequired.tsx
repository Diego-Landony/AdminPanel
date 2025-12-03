import * as React from 'react';
import { cn } from '@/lib/utils';

interface LabelWithRequiredProps extends React.LabelHTMLAttributes<HTMLLabelElement> {
    children: React.ReactNode;
    required?: boolean;
    className?: string;
    htmlFor?: string;
}

export function LabelWithRequired({
    children,
    required,
    htmlFor,
    className,
    ...props
}: LabelWithRequiredProps) {
    return (
        <label
            htmlFor={htmlFor}
            className={cn(
                'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
                className
            )}
            {...props}
        >
            {children}
            {required && (
                <span className="ml-1 text-destructive" aria-hidden="true">*</span>
            )}
            {required && <span className="sr-only">(required)</span>}
        </label>
    );
}

export default LabelWithRequired;
