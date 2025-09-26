import { LucideIcon } from 'lucide-react';
import React from 'react';

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
 * Professional reusable entity info cell component for table rows
 *
 * Provides consistent display pattern across all entity tables including:
 * - Circular icon container with brand theming
 * - Primary text with proper truncation
 * - Secondary text for additional context
 * - Optional badge support for status indicators
 * - Professional spacing and typography
 *
 * @param icon - Lucide icon component to display in circular container
 * @param primaryText - Main entity name/title (e.g., user name, product title)
 * @param secondaryText - Subtitle/description (e.g., email, category)
 * @param badges - Optional React nodes for status badges or indicators
 * @param className - Additional CSS classes for customization
 *
 * @example
 * ```tsx
 * <EntityInfoCell
 *   icon={Users}
 *   primaryText="John Doe"
 *   secondaryText="john@example.com"
 *   badges={<Badge variant="success">Active</Badge>}
 * />
 * ```
 */
export const EntityInfoCell: React.FC<EntityInfoCellProps> = ({ icon: IconComponent, primaryText, secondaryText, badges, className = '' }) => {
    return (
        <div className={`flex items-center gap-3 ${className}`}>
            <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                <IconComponent className="h-5 w-5 text-primary" />
            </div>
            <div className="min-w-0 flex-1">
                <div className="line-clamp-2 text-sm font-medium break-words text-foreground">{primaryText}</div>
                <div className="line-clamp-1 text-sm break-words text-muted-foreground">{secondaryText}</div>
                {badges && <div className="mt-2 flex flex-wrap gap-1">{badges}</div>}
            </div>
        </div>
    );
};
