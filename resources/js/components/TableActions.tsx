import React from 'react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Edit, Trash2 } from 'lucide-react';

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
 * Replaces the complex ActionsMenu with simple, clear actions
 */
export const TableActions: React.FC<TableActionsProps> = ({
    editHref,
    onDelete,
    isDeleting = false,
    canDelete = true,
    editTooltip = "Editar",
    deleteTooltip = "Eliminar",
    showEdit = true,
    showDelete = true,
    className = ""
}) => {
    return (
        <TooltipProvider>
            <div className={`flex items-center justify-end gap-1 ${className}`}>
                {/* Edit Button */}
                {showEdit && editHref && (
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                                asChild
                            >
                                <Link href={editHref}>
                                    <Edit className="h-4 w-4" />
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
                            >
                                {isDeleting ? (
                                    <div className="h-4 w-4 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent" />
                                ) : (
                                    <Trash2 className="h-4 w-4" />
                                )}
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>{canDelete ? deleteTooltip : "No se puede eliminar"}</p>
                        </TooltipContent>
                    </Tooltip>
                )}
            </div>
        </TooltipProvider>
    );
};