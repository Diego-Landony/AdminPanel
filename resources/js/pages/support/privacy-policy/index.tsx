import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { SimpleTable } from '@/components/SimpleTable';
import { TableActions } from '@/components/TableActions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { showNotification } from '@/hooks/useNotifications';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { Check, History, Shield } from 'lucide-react';
import { useState } from 'react';

interface LegalDocument {
    id: number;
    type: string;
    version: string;
    content_json: object;
    content_html: string;
    is_published: boolean;
    published_at: string | null;
    created_at: string;
    creator_name: string;
}

interface PrivacyPageProps {
    documents: LegalDocument[];
    published: LegalDocument | null;
    stats: {
        total: number;
        published: number;
    };
}

export default function PrivacyIndex({ documents, published, stats }: PrivacyPageProps) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [selectedDoc, setSelectedDoc] = useState<LegalDocument | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const openDeleteDialog = (doc: LegalDocument) => {
        setSelectedDoc(doc);
        setShowDeleteDialog(true);
    };

    const closeDeleteDialog = () => {
        setSelectedDoc(null);
        setShowDeleteDialog(false);
        setDeletingId(null);
    };

    const handleDelete = () => {
        if (!selectedDoc) return;

        setDeletingId(selectedDoc.id);
        router.delete(`/support/privacy-policy/${selectedDoc.id}`, {
            preserveState: false,
            onBefore: () => closeDeleteDialog(),
            onError: (error) => {
                setDeletingId(null);
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
        });
    };

    const handlePublish = (doc: LegalDocument) => {
        router.post(
            `/support/privacy-policy/${doc.id}/publish`,
            {},
            {
                preserveState: false,
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
            },
        );
    };

    const docStats = [
        {
            title: 'versiones',
            value: stats.total,
            icon: <History className="h-4 w-4 text-primary" />,
        },
        {
            title: 'publicado',
            value: stats.published,
            icon: <Check className="h-4 w-4 text-green-600" />,
        },
    ];

    const columns = [
        {
            key: 'version',
            title: 'Versión',
            width: 'w-24',
            render: (doc: LegalDocument) => (
                <div className="flex items-center gap-2">
                    <Badge variant="outline">v{doc.version}</Badge>
                    {doc.is_published && (
                        <Badge variant="default" className="bg-green-600">
                            Publicado
                        </Badge>
                    )}
                </div>
            ),
        },
        {
            key: 'creator',
            title: 'Creado por',
            width: 'w-32',
            render: (doc: LegalDocument) => <span className="text-sm text-muted-foreground">{doc.creator_name}</span>,
        },
        {
            key: 'created_at',
            title: 'Fecha',
            width: 'w-40',
            render: (doc: LegalDocument) => (
                <span className="text-sm text-muted-foreground">{new Date(doc.created_at).toLocaleDateString('es-GT')}</span>
            ),
        },
        {
            key: 'published_at',
            title: 'Publicado',
            width: 'w-40',
            render: (doc: LegalDocument) => (
                <span className="text-sm text-muted-foreground">
                    {doc.published_at ? new Date(doc.published_at).toLocaleDateString('es-GT') : '-'}
                </span>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'w-32',
            textAlign: 'right' as const,
            render: (doc: LegalDocument) => (
                <div className="flex items-center justify-end gap-2">
                    {!doc.is_published && (
                        <Button size="sm" variant="outline" onClick={() => handlePublish(doc)}>
                            Publicar
                        </Button>
                    )}
                    <TableActions
                        editHref={`/support/privacy-policy/${doc.id}/edit`}
                        onDelete={doc.is_published ? undefined : () => openDeleteDialog(doc)}
                        isDeleting={deletingId === doc.id}
                        editTooltip="Editar documento"
                        deleteTooltip="Eliminar documento"
                    />
                </div>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Política de Privacidad" />

            <SimpleTable
                title="Política de Privacidad"
                description="Gestiona las versiones de la política de privacidad de la app"
                icon={<Shield className="h-5 w-5" />}
                data={documents}
                columns={columns}
                stats={docStats}
                createUrl="/support/privacy-policy/create"
                createLabel="Nueva versión"
                emptyMessage="No hay versiones de política de privacidad"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDelete}
                isDeleting={deletingId !== null}
                entityName={selectedDoc ? `Versión ${selectedDoc.version}` : ''}
                entityType="documento"
            />
        </AppLayout>
    );
}
