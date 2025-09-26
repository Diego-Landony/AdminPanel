import React from 'react';

interface AvatarColumnProps {
    icon: React.ReactNode;
    title: string;
    subtitle?: string;
    badges?: React.ReactNode[];
}

export function AvatarColumn({ icon, title, subtitle, badges }: AvatarColumnProps) {
    return (
        <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">{icon}</div>
            <div className="min-w-0">
                <div className="truncate text-sm font-medium text-foreground">{title}</div>
                {subtitle && <div className="truncate text-sm text-muted-foreground">{subtitle}</div>}
                {badges && badges.length > 0 && <div className="mt-1 flex flex-wrap items-center gap-2">{badges}</div>}
            </div>
        </div>
    );
}
