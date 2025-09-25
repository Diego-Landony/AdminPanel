import React from 'react';
import { LucideIcon } from 'lucide-react';

interface EntityInfoCellProps {
    /** Icon component to display */
    icon: LucideIcon;
    /** Primary text (usually the entity name/title) */
    primaryText: string;
    /** Secondary text (usually subtitle/description) */
    secondaryText: string;
    /** Optional badges to display below the text */
    badges?: React.ReactNode;
    /** Additional CSS classes */
    className?: string;
}

/**
 * Professional reusable entity info cell component
 * Used consistently across all table rows to display:
 * - Icon in circular container
 * - Primary text (name/title)
 * - Secondary text (subtitle/description)
 * - Optional badges
 */
export const EntityInfoCell: React.FC<EntityInfoCellProps> = ({
    icon: IconComponent,
    primaryText,
    secondaryText,
    badges,
    className = ""
}) => {
    return (
        <div className={`flex items-center gap-3 ${className}`}>
            <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                <IconComponent className="w-5 h-5 text-primary" />
            </div>
            <div className="min-w-0 flex-1">
                <div className="font-medium text-sm text-foreground break-words line-clamp-2">
                    {primaryText}
                </div>
                <div className="text-sm text-muted-foreground break-words line-clamp-1">
                    {secondaryText}
                </div>
                {badges && (
                    <div className="flex flex-wrap gap-1 mt-2">
                        {badges}
                    </div>
                )}
            </div>
        </div>
    );
};