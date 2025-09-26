import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Trash2 } from 'lucide-react';
import React from 'react';

interface DeleteConfirmationDialogProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    isDeleting: boolean;
    entityName: string;
    entityType: string;
    customMessage?: string;
    canDelete?: boolean;
    deleteBlockedReason?: string;
}

/**
 * Professional delete confirmation dialog component
 * Provides consistent UX for delete operations across all entities
 */
export const DeleteConfirmationDialog: React.FC<DeleteConfirmationDialogProps> = ({
    isOpen,
    onClose,
    onConfirm,
    isDeleting,
    entityName,
    entityType,
    customMessage,
    canDelete = true,
    deleteBlockedReason,
}) => {
    const defaultMessage = `¿Estás seguro de que quieres eliminar ${entityType.toLowerCase()} "${entityName}"? Esta acción no se puede deshacer.`;

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirmar eliminación</DialogTitle>
                    <DialogDescription>
                        {customMessage || defaultMessage}
                        {!canDelete && deleteBlockedReason && <span className="mt-2 block text-destructive">{deleteBlockedReason}</span>}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={isDeleting}>
                        Cancelar
                    </Button>
                    <Button variant="destructive" onClick={onConfirm} disabled={isDeleting || !canDelete}>
                        {isDeleting ? (
                            <>
                                <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                Eliminando...
                            </>
                        ) : (
                            <>
                                <Trash2 className="mr-2 h-4 w-4" />
                                Eliminar
                            </>
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};
