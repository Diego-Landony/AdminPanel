import { LucideIcon } from 'lucide-react';
import React from 'react';

interface EntityInfoCellProps {
    /** Icon component to display */
    icon?: LucideIcon;
    /** Image URL to display instead of icon */
    image?: string | null;
    /** Primary text (usually the entity name/title) */
    primaryText: string;
    /** Secondary text (usually subtitle/description) */
    secondaryText?: string;
    /** Optional badges to display below the text */
    badges?: React.ReactNode;
    /** Additional CSS classes */
    className?: string;
}

/**
 * Professional reusable entity info cell component for table rows
 *
 * Provides consistent display pattern across all entity tables including:
 * - Circular icon/image container with brand theming
 * - Primary text with proper truncation
 * - Secondary text for additional context (optional)
 * - Optional badge support for status indicators
 * - Professional spacing and typography
 *
 * @param icon - Lucide icon component to display in circular container (optional)
 * @param image - Image URL to display instead of icon (optional)
 * @param primaryText - Main entity name/title (e.g., user name, product title)
 * @param secondaryText - Subtitle/description (e.g., email, category) (optional)
 * @param badges - Optional React nodes for status badges or indicators
 * @param className - Additional CSS classes for customization
 *
 * @example
 * ```tsx
 * // With icon
 * <EntityInfoCell
 *   icon={Users}
 *   primaryText="John Doe"
 *   secondaryText="john@example.com"
 *   badges={<Badge variant="success">Active</Badge>}
 * />
 *
 * // With image
 * <EntityInfoCell
 *   image="/path/to/image.jpg"
 *   primaryText="Product Name"
 *   badges={<Badge variant="success">Active</Badge>}
 * />
 * ```
 */
export const EntityInfoCell: React.FC<EntityInfoCellProps> = ({ icon: IconComponent, image, primaryText, secondaryText, badges, className = '' }) => {
    return (
        <div className={`flex items-center gap-3 ${className}`}>
            {(IconComponent || image) && (
                <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10">
                    {image ? (
                        <img src={image} alt={primaryText} className="h-full w-full object-cover" />
                    ) : IconComponent ? (
                        <IconComponent className="h-5 w-5 text-primary" />
                    ) : null}
                </div>
            )}
            <div className="min-w-0 flex-1">
                <div className="line-clamp-2 text-sm font-medium break-words text-foreground">{primaryText}</div>
                {secondaryText && <div className="line-clamp-1 text-sm break-words text-muted-foreground">{secondaryText}</div>}
                {badges && <div className="mt-2 flex flex-wrap gap-1">{badges}</div>}
            </div>
        </div>
    );
};
