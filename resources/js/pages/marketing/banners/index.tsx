import { showNotification } from '@/hooks/useNotifications';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { DeleteConfirmationDialog } from '@/components/DeleteConfirmationDialog';
import { SortableTable } from '@/components/SortableTable';
import { StandardMobileCard } from '@/components/StandardMobileCard';
import { TableActions } from '@/components/TableActions';
import { ACTIVE_STATUS_CONFIGS, StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { PLACEHOLDERS } from '@/constants/ui-constants';
import AppLayout from '@/layouts/app-layout';
import { Check, Image, Monitor, Smartphone, X } from 'lucide-react';

interface Banner {
    id: number;
    title: string;
    description: string | null;
    image: string;
    image_url: string | null;
    orientation: 'horizontal' | 'vertical';
    display_seconds: number;
    link_type: string | null;
    validity_type: 'permanent' | 'date_range' | 'weekdays';
    valid_from: string | null;
    valid_until: string | null;
    weekdays: number[] | null;
    is_active: boolean;
    is_valid_now: boolean;
    sort_order: number;
}

interface BannersPageProps {
    banners: Banner[];
    stats: {
        total: number;
        active: number;
        horizontal: number;
        vertical: number;
    };
}

const VALIDITY_LABELS: Record<string, string> = {
    permanent: 'Permanente',
    date_range: 'Por fechas',
    weekdays: 'Por días',
};

export default function BannersIndex({ banners, stats }: BannersPageProps) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [selectedBanner, setSelectedBanner] = useState<Banner | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const handleReorder = (reordered: Banner[]) => {
        setIsSaving(true);

        const orderData = reordered.map((item, index) => ({
            id: item.id,
            sort_order: index + 1,
        }));

        router.post(
            route('marketing.banners.reorder'),
            { banners: orderData },
            {
                preserveState: true,
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
                onFinish: () => setIsSaving(false),
            },
        );
    };

    const handleRefresh = () => router.reload();

    const openDeleteDialog = (banner: Banner) => {
        setSelectedBanner(banner);
        setShowDeleteDialog(true);
    };

    const closeDeleteDialog = () => {
        setSelectedBanner(null);
        setShowDeleteDialog(false);
        setDeletingId(null);
    };

    const handleDelete = () => {
        if (!selectedBanner) return;

        setDeletingId(selectedBanner.id);
        router.delete(`/marketing/banners/${selectedBanner.id}`, {
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

    const handleToggle = (banner: Banner) => {
        router.post(
            `/marketing/banners/${banner.id}/toggle`,
            {},
            {
                preserveState: true,
                onError: (error) => {
                    if (error.message) {
                        showNotification.error(error.message);
                    }
                },
            },
        );
    };

    const bannerStats = [
        {
            title: 'banners',
            value: stats.total,
            icon: <Image className="h-4 w-4 text-primary" />,
        },
        {
            title: 'activos',
            value: stats.active,
            icon: <Check className="h-4 w-4 text-green-600" />,
        },
        {
            title: 'horizontal',
            value: stats.horizontal,
            icon: <Monitor className="h-4 w-4 text-blue-600" />,
        },
        {
            title: 'vertical',
            value: stats.vertical,
            icon: <Smartphone className="h-4 w-4 text-purple-600" />,
        },
    ];

    const columns = [
        {
            key: 'preview',
            title: 'Vista Previa',
            width: 'w-24',
            render: (banner: Banner) => (
                <div className="relative h-14 w-20 overflow-hidden rounded border bg-muted">
                    {banner.image_url ? (
                        <img src={banner.image_url} alt={banner.title} className="h-full w-full object-cover" />
                    ) : (
                        <div className="flex h-full w-full items-center justify-center">
                            <Image className="h-6 w-6 text-muted-foreground" />
                        </div>
                    )}
                </div>
            ),
        },
        {
            key: 'title',
            title: 'Título',
            width: 'w-48',
            render: (banner: Banner) => (
                <div>
                    <div className="text-sm font-medium text-foreground">{banner.title}</div>
                    {banner.description && <div className="text-xs text-muted-foreground line-clamp-1">{banner.description}</div>}
                </div>
            ),
        },
        {
            key: 'orientation',
            title: 'Orientación',
            width: 'w-28',
            align: 'center' as const,
            render: (banner: Banner) => (
                <Badge variant="outline" className="capitalize">
                    {banner.orientation === 'horizontal' ? <Monitor className="mr-1 h-3 w-3" /> : <Smartphone className="mr-1 h-3 w-3" />}
                    {banner.orientation}
                </Badge>
            ),
        },
        {
            key: 'validity',
            title: 'Validez',
            width: 'w-28',
            align: 'center' as const,
            render: (banner: Banner) => (
                <div className="flex flex-col items-center gap-1">
                    <span className="text-xs text-muted-foreground">{VALIDITY_LABELS[banner.validity_type]}</span>
                    {banner.is_valid_now ? (
                        <Badge variant="outline" className="border-green-200 bg-green-50 text-green-700 text-xs">
                            Visible ahora
                        </Badge>
                    ) : (
                        <Badge variant="outline" className="border-orange-200 bg-orange-50 text-orange-700 text-xs">
                            No visible
                        </Badge>
                    )}
                </div>
            ),
        },
        {
            key: 'status',
            title: 'Estado',
            width: 'w-24',
            align: 'center' as const,
            render: (banner: Banner) => (
                <button onClick={() => handleToggle(banner)} className="cursor-pointer" title="Click para cambiar estado">
                    <StatusBadge status={banner.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />
                </button>
            ),
        },
        {
            key: 'actions',
            title: 'Acciones',
            width: 'w-24',
            align: 'right' as const,
            render: (banner: Banner) => (
                <TableActions
                    editHref={`/marketing/banners/${banner.id}/edit`}
                    onDelete={() => openDeleteDialog(banner)}
                    isDeleting={deletingId === banner.id}
                    editTooltip="Editar banner"
                    deleteTooltip="Eliminar banner"
                />
            ),
        },
    ];

    const renderMobileCard = (banner: Banner) => (
        <StandardMobileCard
            title={banner.title}
            subtitle={banner.description || `${banner.orientation} • ${banner.display_seconds}s`}
            image={banner.image_url}
            badge={{
                children: <StatusBadge status={banner.is_active ? 'active' : 'inactive'} configs={ACTIVE_STATUS_CONFIGS} showIcon={false} />,
            }}
            actions={{
                editHref: `/marketing/banners/${banner.id}/edit`,
                onDelete: () => openDeleteDialog(banner),
                isDeleting: deletingId === banner.id,
                editTooltip: 'Editar banner',
                deleteTooltip: 'Eliminar banner',
            }}
            dataFields={[
                {
                    label: 'Orientación',
                    value: (
                        <Badge variant="outline" className="capitalize">
                            {banner.orientation}
                        </Badge>
                    ),
                },
                {
                    label: 'Validez',
                    value: VALIDITY_LABELS[banner.validity_type],
                },
                {
                    label: 'Visible',
                    value: banner.is_valid_now ? 'Sí' : 'No',
                },
            ]}
        />
    );

    return (
        <AppLayout>
            <Head title="Banners Promocionales" />

            <SortableTable
                title="Banners Promocionales"
                description="Gestiona los banners del carrusel de la app"
                data={banners}
                columns={columns}
                stats={bannerStats}
                createUrl="/marketing/banners/create"
                createLabel="Crear"
                searchable={true}
                searchPlaceholder={PLACEHOLDERS.search}
                onReorder={handleReorder}
                onRefresh={handleRefresh}
                isSaving={isSaving}
                renderMobileCard={renderMobileCard}
                breakpoint="lg"
            />

            <DeleteConfirmationDialog
                isOpen={showDeleteDialog}
                onClose={closeDeleteDialog}
                onConfirm={handleDelete}
                isDeleting={deletingId !== null}
                entityName={selectedBanner?.title || ''}
                entityType="banner"
            />
        </AppLayout>
    );
}
