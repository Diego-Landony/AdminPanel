import { CardActions, DataField, ResponsiveCard, ResponsiveCardContent, ResponsiveCardHeader } from '@/components/CardLayout';
import { TableActions } from '@/components/TableActions';
import { LucideIcon } from 'lucide-react';
import React, { memo } from 'react';

export interface DataFieldConfig {
    label: string;
    value: React.ReactNode;
    condition?: boolean; // Optional condition to show/hide the field
}

export interface StandardMobileCardProps {
    /** Icon component to display in header */
    icon: LucideIcon;
    /** Primary title (entity name) */
    title: React.ReactNode;
    /** Secondary subtitle text */
    subtitle: React.ReactNode;
    /** Badge configuration for status/state */
    badge?: {
        children: React.ReactNode;
        variant?: 'default' | 'secondary' | 'destructive' | 'outline';
        className?: string;
    };
    /** Array of data fields to display */
    dataFields: DataFieldConfig[];
    /** Actions configuration */
    actions?: {
        editHref?: string;
        onDelete?: () => void;
        isDeleting?: boolean;
        canDelete?: boolean;
        editTooltip?: string;
        deleteTooltip?: string;
        showEdit?: boolean;
        showDelete?: boolean;
    };
    /** Additional content to render after actions */
    additionalContent?: React.ReactNode;
    /** Custom CSS classes */
    className?: string;
}

/**
 * Professional standardized mobile card component for entity display
 *
 * Unifies the ResponsiveCard + DataFields pattern used across all mobile interfaces.
 * Provides consistent layout and interaction patterns for all entity types.
 *
 * Features:
 * - Standardized header with icon, title, subtitle, and status badge
 * - Flexible data fields with conditional rendering support
 * - Integrated TableActions for CRUD operations
 * - Professional spacing, typography, and responsive behavior
 * - Support for additional custom content and interactions
 * - Consistent theming with dark mode support
 *
 * @param icon - Lucide icon component for entity type identification
 * @param title - Primary entity name/title (supports React nodes for complex content)
 * @param subtitle - Secondary information (e.g., email, description)
 * @param badge - Optional status badge configuration
 * @param dataFields - Array of labeled data fields with conditional rendering
 * @param actions - Edit/delete action configuration with permissions
 * @param additionalContent - Custom content rendered after actions
 * @param className - Additional CSS classes for customization
 *
 * @example
 * ```tsx
 * <StandardMobileCard
 *   icon={Users}
 *   title="John Doe"
 *   subtitle="john@example.com"
 *   badge={{ children: <StatusBadge status="active" /> }}
 *   dataFields={[
 *     { label: "Role", value: "Admin" },
 *     { label: "Phone", value: user.phone, condition: !!user.phone }
 *   ]}
 *   actions={{
 *     editHref: `/users/${user.id}/edit`,
 *     onDelete: () => handleDelete(user.id),
 *     canDelete: !user.is_system
 *   }}
 * />
 * ```
 */
const StandardMobileCardComponent: React.FC<StandardMobileCardProps> = ({
    icon: IconComponent,
    title,
    subtitle,
    badge,
    dataFields,
    actions,
    additionalContent,
    className = '',
}) => {
    return (
        <ResponsiveCard className={className}>
            <ResponsiveCardHeader icon={<IconComponent className="h-4 w-4 text-primary" />} title={title} subtitle={subtitle} badge={badge} />

            <ResponsiveCardContent layout="stack">
                {dataFields.map((field, index) => {
                    // Skip field if condition is provided and false
                    if (field.condition !== undefined && !field.condition) {
                        return null;
                    }

                    return <DataField key={`${field.label}-${index}`} label={field.label} value={field.value} truncate={false} />;
                })}
            </ResponsiveCardContent>

            {(actions || additionalContent) && (
                <CardActions>
                    {actions && (
                        <TableActions
                            editHref={actions.editHref}
                            onDelete={actions.onDelete}
                            isDeleting={actions.isDeleting ?? false}
                            canDelete={actions.canDelete ?? true}
                            editTooltip={actions.editTooltip ?? 'Editar'}
                            deleteTooltip={actions.deleteTooltip ?? 'Eliminar'}
                            showEdit={actions.showEdit ?? true}
                            showDelete={actions.showDelete ?? true}
                        />
                    )}
                    {additionalContent}
                </CardActions>
            )}
        </ResponsiveCard>
    );
};

// Memoized export for performance optimization
export const StandardMobileCard = memo(StandardMobileCardComponent);
