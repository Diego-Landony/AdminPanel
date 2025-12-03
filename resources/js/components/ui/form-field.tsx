import { cn } from '@/lib/utils';
import { type HTMLAttributes, type ReactNode } from 'react';
import { FormError } from './form-error';
import { LabelWithRequired } from '@/components/LabelWithRequired';

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
                <LabelWithRequired required={required}>
                    {label}
                </LabelWithRequired>
            )}
            
            {description && (
                <p className="text-sm text-muted-foreground">{description}</p>
            )}
            
            {children}
            
            {error && <FormError message={error} />}
        </div>
    );
}
