import { cn } from '@/lib/utils';
import { type HTMLAttributes, type ReactNode } from 'react';
import { FormError } from './form-error';

interface FormFieldProps extends HTMLAttributes<HTMLDivElement> {
    label?: string;
    error?: string;
    required?: boolean;
    children: ReactNode;
    description?: string;
}

export function FormField({ 
    label, 
    error, 
    required = false,
    children, 
    description,
    className = '', 
    ...props 
}: FormFieldProps) {
    return (
        <div className={cn('space-y-2', className)} {...props}>
            {label && (
                <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    {label}
                    {required && <span className="text-red-500 ml-1">*</span>}
                </label>
            )}
            
            {description && (
                <p className="text-sm text-muted-foreground">{description}</p>
            )}
            
            {children}
            
            {error && <FormError message={error} />}
        </div>
    );
}
