import React from 'react';

interface FormSectionProps {
    title: string;
    description: string;
    icon?: React.ReactNode;
    children: React.ReactNode;
}

export function FormSection({ title, description, icon, children }: FormSectionProps) {
    return (
        <div className="space-y-6">
            <div className="border-b pb-4">
                <h2 className="text-lg font-semibold flex items-center gap-2">
                    {icon}
                    {title}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {description}
                </p>
            </div>
            {children}
        </div>
    );
}