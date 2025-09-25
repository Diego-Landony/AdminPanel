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