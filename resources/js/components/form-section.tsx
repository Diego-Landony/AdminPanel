import { cn } from '@/lib/utils';
import React from 'react';

interface FormSectionProps {
    icon?: React.ComponentType<React.SVGProps<SVGSVGElement>>;
    title: string;
    description?: string;
    children: React.ReactNode;
    className?: string;
}

export function FormSection({ icon: Icon, title, description, children, className }: FormSectionProps) {
    return (
        <div className={cn('space-y-4', className)}>
            <div className="space-y-2">
                <h2 className="flex items-center gap-2 truncate text-lg font-semibold">
                    {Icon && <Icon className="h-5 w-5 flex-shrink-0" />}
                    <span className="truncate">{title}</span>
                </h2>
                {description && <p className="text-sm break-words text-muted-foreground">{description}</p>}
            </div>
            <div className="space-y-4 overflow-hidden">{children}</div>
        </div>
    );
}
