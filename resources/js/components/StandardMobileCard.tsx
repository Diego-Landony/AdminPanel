import React from 'react';
import { LucideIcon } from 'lucide-react';
import {
    ResponsiveCard,
    ResponsiveCardHeader,
    ResponsiveCardContent,
    DataField,
    CardActions
} from '@/components/CardLayout';
import { TableActions } from '@/components/TableActions';

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
 * Professional standardized mobile card component
 * Unifies the ResponsiveCard + DataFields pattern used across all mobile cards
 *
 * Features:
 * - Consistent header with icon, title, subtitle, and badge
 * - Flexible data fields with conditional rendering
 * - Integrated TableActions for edit/delete operations
 * - Support for additional custom content
 * - Professional styling and responsive design
 */
export const StandardMobileCard: React.FC<StandardMobileCardProps> = ({
    icon: IconComponent,
    title,
    subtitle,
    badge,
    dataFields,
    actions,
    additionalContent,
    className = ""
}) => {
    return (
        <ResponsiveCard className={className}>
            <ResponsiveCardHeader
                icon={<IconComponent className="w-4 h-4 text-primary" />}
                title={title}
                subtitle={subtitle}
                badge={badge}
            />

            <ResponsiveCardContent>
                {dataFields.map((field, index) => {
                    // Skip field if condition is provided and false
                    if (field.condition !== undefined && !field.condition) {
                        return null;
                    }

                    return (
                        <DataField
                            key={`${field.label}-${index}`}
                            label={field.label}
                            value={field.value}
                        />
                    );
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
                            editTooltip={actions.editTooltip ?? "Editar"}
                            deleteTooltip={actions.deleteTooltip ?? "Eliminar"}
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