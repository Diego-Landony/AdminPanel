import { EditPageSkeleton } from './EditPageSkeleton';

export function EditRolesSkeleton() {
    return (
        <EditPageSkeleton
            sections={[
                { fields: 2, hasTextarea: true }, // Información básica del rol
                { fields: 0, hasTable: true, showIcon: true, showTitle: true }, // Gestión de usuarios y permisos
            ]}
            showBackButton={true}
            showSubmitButton={true}
            showResetButton={false}
            showInfoCard={false}
        />
    );
}
