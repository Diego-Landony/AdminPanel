import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Link } from '@inertiajs/react';
import { Edit, Trash2 } from 'lucide-react';
import React from 'react';

interface TableActionsProps {
    /** URL for edit action */
    editHref?: string;
    /** Function called when delete is clicked */
    onDelete?: () => void;
    /** Whether delete action is currently loading */
    isDeleting?: boolean;
    /** Whether delete action should be disabled */
    canDelete?: boolean;
    /** Tooltip text for edit button */
    editTooltip?: string;
    /** Tooltip text for delete button */
    deleteTooltip?: string;
    /** Whether to show edit action */
    showEdit?: boolean;
    /** Whether to show delete action */
    showDelete?: boolean;
    /** Additional CSS classes */
    className?: string;
}

/**
 * Professional table actions component with direct Edit/Delete buttons
 *
 * Provides consistent action interface across all data tables. Replaces complex
 * dropdown menus with clear, accessible button actions.
 *
 * Features:
 * - Direct edit/delete buttons with tooltips
 * - Permission-based action visibility
 * - Loading states with spinner indicators
 * - Accessible keyboard navigation
 * - Consistent hover states and theming
 * - Integration with Inertia.js routing
 *
 * @param editHref - URL for edit action (if provided, shows edit button)
 * @param onDelete - Callback function for delete action
 * @param isDeleting - Whether delete operation is in progress
 * @param canDelete - Whether delete action should be enabled
 * @param editTooltip - Tooltip text for edit button
 * @param deleteTooltip - Tooltip text for delete button
 * @param showEdit - Whether to display edit button
 * @param showDelete - Whether to display delete button
 * @param className - Additional CSS classes
 *
 * @example
 * ```tsx
 * <TableActions
 *   editHref={`/users/${user.id}/edit`}
 *   onDelete={() => handleDelete(user.id)}
 *   isDeleting={deletingUserId === user.id}
 *   canDelete={!user.is_system}
 *   editTooltip="Edit user"
 *   deleteTooltip="Delete user"
 * />
 * ```
 */
export const TableActions: React.FC<TableActionsProps> = ({
    editHref,
    onDelete,
    isDeleting = false,
    canDelete = true,
    editTooltip = 'Editar',
    deleteTooltip = 'Eliminar',
    showEdit = true,
    showDelete = true,
    className = '',
}) => {
    return (
        <TooltipProvider>
            <div className={`flex items-center justify-end gap-1 ${className}`}>
                {/* Edit Button */}
                {showEdit && editHref && (
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-muted-foreground hover:text-foreground" asChild>
                                <Link href={editHref} aria-label={editTooltip}>
                                    <Edit className="h-4 w-4" alt="Editar" />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>{editTooltip}</p>
                        </TooltipContent>
                    </Tooltip>
                )}

                {/* Delete Button */}
                {showDelete && onDelete && (
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-8 w-8 p-0 text-muted-foreground hover:text-destructive disabled:opacity-50"
                                onClick={onDelete}
                                disabled={isDeleting || !canDelete}
                                aria-label={canDelete ? deleteTooltip : 'No se puede eliminar'}
                            >
                                {isDeleting ? (
                                    <div className="h-4 w-4 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent" aria-label="Eliminando..." />
                                ) : (
                                    <Trash2 className="h-4 w-4" alt="Eliminar" />
                                )}
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>{canDelete ? deleteTooltip : 'No se puede eliminar'}</p>
                        </TooltipContent>
                    </Tooltip>
                )}
            </div>
        </TooltipProvider>
    );
};
