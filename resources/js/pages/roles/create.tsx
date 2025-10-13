import { showNotification } from '@/hooks/useNotifications';
import { useForm } from '@inertiajs/react';
import React from 'react';

import { CreatePageLayout } from '@/components/create-page-layout';
import { FormSection } from '@/components/form-section';
import { PermissionsTable } from '@/components/PermissionsTable';
import { CreateRolesSkeleton } from '@/components/skeletons';
import { FormError } from '@/components/ui/form-error';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { ENTITY_ICONS } from '@/constants/section-icons';
import { NOTIFICATIONS } from '@/constants/ui-constants';

/**
 * Interfaz para los permisos agrupados
 */
interface Permission {
    id: number;
    name: string;
    display_name: string;
    description: string | null;
    group: string;
}

/**
 * Props de la página
 */
interface CreateRolePageProps {
    permissions: Record<string, Permission[]>;
}

export default function CreateRole({ permissions }: CreateRolePageProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        permissions: [] as string[],
    });

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/roles', {
            onSuccess: () => {
                // Éxito manejado automáticamente por el layout
            },
            onError: (errors) => {
                // Los errores de validación se muestran automáticamente
                // Los errores del servidor se manejan por el layout
                if (Object.keys(errors).length === 0) {
                    showNotification.error(NOTIFICATIONS.error.serverRoleCreate);
                }
            },
        });
    };

    /**
     * Maneja la selección/deselección de permisos
     */
    const handlePermissionChange = (permissionName: string, checked: boolean) => {
        if (checked) {
            setData('permissions', [...data.permissions, permissionName]);
        } else {
            setData(
                'permissions',
                data.permissions.filter((p) => p !== permissionName),
            );
        }
    };


    return (
        <CreatePageLayout
            title="Nuevo Rol"
            backHref="/roles"
            onSubmit={handleSubmit}
            processing={processing}
            pageTitle="Crear Rol"
            loading={processing}
            loadingSkeleton={CreateRolesSkeleton}
        >
            <FormSection icon={ENTITY_ICONS.role.info} title="Información del Rol">
                <FormField label="Nombre del Rol" error={errors.name} required>
                    <Input id="name" type="text" value={data.name} onChange={(e) => setData('name', e.target.value)}  />
                </FormField>

                <FormField label="Descripción" error={errors.description}>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        className="min-h-[100px]"
                    />
                </FormField>
            </FormSection>

            <FormSection
                icon={ENTITY_ICONS.role.permissions}
                title="Permisos del Rol"
                description="Selecciona las acciones que este rol puede realizar"
            >
                <PermissionsTable
                    selectedPermissions={data.permissions}
                    onPermissionChange={handlePermissionChange}
                    permissions={permissions}
                />
                {errors.permissions && <FormError message={errors.permissions} />}
            </FormSection>
        </CreatePageLayout>
    );
}
