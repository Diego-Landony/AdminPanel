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
            <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                {icon}
            </div>
            <div className="min-w-0">
                <div className="font-medium text-sm text-foreground truncate">
                    {title}
                </div>
                {subtitle && (
                    <div className="text-sm text-muted-foreground truncate">
                        {subtitle}
                    </div>
                )}
                {badges && badges.length > 0 && (
                    <div className="flex flex-wrap items-center gap-2 mt-1">
                        {badges}
                    </div>
                )}
            </div>
        </div>
    );
}