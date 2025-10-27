import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { SortableTable } from '@/components/SortableTable';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import AppLayout from '@/layouts/app-layout';
import { ListChecks, Package, Star } from 'lucide-react';

interface Section {
    id: number;
    title: string;
    description: string | null;
    is_required: boolean;
    allow_multiple: boolean;
    min_selections: number;
    max_selections: number;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

interface SectionsPageProps {
    sections: Section[];
    stats: {
        total_sections: number;
        required_sections: number;
        total_options: number;
    };
}

export default function SectionsIndex({ sections, stats }: SectionsPageProps) {
    const [deletingSection, setDeletingSection] = useState<number | null>(null);
    const [selectedSection, setSelectedSection] = useState<Section | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const handleReorder = (reorderedSections: Section[]) => {
        setIsSaving(true);

        const orderData = reorderedSections.map((section, index) => ({
            id: section.id,
            sort_order: index + 1,
        }));

        router.post(
            route('menu.sections.reorder'),
            { sections: orderData },
            {
                preserveState: true,
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
                onFinish: () => {
                    setIsSaving(false);
                },
            },
        );
    };

    const handleRefresh = () => {
        router.reload();
    };

    const openDeleteDialog = (section: Section) => {
        setSelectedSection(section);
        setShowDeleteDialog(true);
    };

    const closeDeleteDialog = () => {
        setSelectedSection(null);
        setShowDeleteDialog(false);
        setDeletingSection(null);
    };

    const handleDeleteSection = () => {
        if (!selectedSection) return;

        setDeletingSection(selectedSection.id);
        router.delete(`/menu/sections/${selectedSection.id}`, {
            preserveState: false,
            onBefore: () => {
                closeDeleteDialog();
            },
            onError: (error) => {
                setDeletingSection(null);
                if (error.message) {
                    showNotification.error(error.message);
                }
            },
        });
    };

    // const formatDate = (dateString: string): string => {
    //     return new Date(dateString).toLocaleDateString('es-ES', {
    //         year: 'numeric',
    //         month: 'short',
    //         day: 'numeric',
    //     });
    // };

    const sectionStats = [
        {
            title: 'secciones',
            value: stats.total_sections,
            icon: <ListChecks className="h-4 w-4 text-primary" />,
        },
        {
            title: 'requeridas',
            value: stats.required_sections,
            icon: <Star className="h-4 w-4 text-green-600" />,
        },
        {
            title: 'opcionales',
            value: stats.total_sections - stats.required_sections,
            icon: <Package className="h-4 w-4 text-gray-600" />,
        },
    ];

    const columns = [
        {
            key: 'name',
            title: 'Sección',
            width: 'w-64',
            render: (section: Section) => <div className="truncate text-sm font-medium text-foreground">{section.title}</div>,
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'w-32',
            textAlign: 'center' as const,
            render: (section: Section) => (
                <div className="flex justify-center">
                    <StatusBadge status={section.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />
                </div>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'w-24',
            textAlign: 'right' as const,
            render: (section: Section) => (
                <TableActions
                    editHref={`/menu/sections/${section.id}/edit`}
                    onDelete={() => openDeleteDialog(section)}
                    isDeleting={deletingSection === section.id}
                    editTooltip="Editar sección"
                    deleteTooltip="Eliminar sección"
                />
            ),
        },
    ];

    const renderMobileCard = (section: Section) => (
        <StandardMobileCard
            title={section.title}
            subtitle={section.description || 'Sin descripción'}
            badge={{
                children: <StatusBadge status={section.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />,
            }}
            actions={{
                editHref: `/menu/sections/${section.id}/edit`,
                onDelete: () => openDeleteDialog(section),
                isDeleting: deletingSection === section.id,
                editTooltip: 'Editar sección',
                deleteTooltip: 'Eliminar sección',
            }}
        />
    );

    return (
        <AppLayout>
            <Head title="Secciones" />

            <SortableTable
                title="Secciones de Menú"
                data={sections}
                columns={columns}
                stats={sectionStats}
                createUrl="/menu/sections/create"
                createLabel="Crear"
                searchable={true}
                searchPlaceholder="Buscar secciones..."
                onReorder={handleReorder}
                onRefresh={handleRefresh}
                isSaving={isSaving}
                renderMobileCard={renderMobileCard}
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDeleteSection}
                isDeleting={deletingSection !== null}
                entityName={selectedSection?.title || ''}
                entityType="sección"
            />
        </AppLayout>
    );
}
