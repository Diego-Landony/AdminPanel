import { EditPageSkeleton } from './EditPageSkeleton';

export function EditRestaurantsSkeleton() {
    return (
        <EditPageSkeleton
            sections={[
                { fields: 6, hasTextarea: true, showIcon: true, showTitle: true }, // Información Básica
                { fields: 4, hasCheckboxes: 3, showIcon: true, showTitle: true }, // Configuración de Servicios
                { fields: 0, hasTable: true, showIcon: true, showTitle: true }, // Horarios de Atención
            ]}
            showBackButton={true}
            showSubmitButton={true}
            showResetButton={false}
            showInfoCard={false}
        />
    );
}
