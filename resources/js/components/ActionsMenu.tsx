import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { LoadingSpinner } from '@/components/ui/loading-spinner';
import { Edit, MoreVertical, Trash2 } from 'lucide-react';
import React from 'react';

interface ActionsMenuProps {
    onEdit?: () => void;
    onDelete?: () => void;
    editHref?: string;
    canEdit?: boolean;
    canDelete?: boolean;
    isDeleting?: boolean;
    editTitle?: string;
    deleteTitle?: string;
}

export function ActionsMenu({
    onEdit,
    onDelete,
    editHref,
    canEdit = true,
    canDelete = true,
    isDeleting = false,
    editTitle = 'Editar',
    deleteTitle = 'Eliminar',
}: ActionsMenuProps) {
    const handleEdit = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();

        if (editHref) {
            window.location.href = editHref;
        } else if (onEdit) {
            onEdit();
        }
    };

    const handleDelete = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();

        if (onDelete) {
            onDelete();
        }
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-8 w-8 p-0 hover:bg-muted" title="M�s opciones">
                    <MoreVertical className="h-4 w-4" />
                    <span className="sr-only">Abrir men�</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-40">
                {canEdit && (
                    <DropdownMenuItem onClick={handleEdit} className="cursor-pointer">
                        <Edit className="mr-2 h-4 w-4" />
                        {editTitle}
                    </DropdownMenuItem>
                )}
                {canDelete && (
                    <DropdownMenuItem onClick={handleDelete} className="cursor-pointer" disabled={isDeleting}>
                        {isDeleting ? (
                            <LoadingSpinner size="sm" variant="current" className="mr-2" />
                        ) : (
                            <Trash2 className="mr-2 h-4 w-4" />
                        )}
                        {isDeleting ? 'Eliminando...' : deleteTitle}
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
