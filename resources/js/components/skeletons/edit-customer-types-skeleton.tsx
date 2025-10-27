import { EditPageSkeleton } from './EditPageSkeleton';

export function EditCustomerTypesSkeleton() {
    return (
        <EditPageSkeleton
            sections={[
                { fields: 5, hasSelect: true, hasCheckboxes: 1, showIcon: true, showTitle: true }, // Información del Tipo
            ]}
            showBackButton={true}
            showSubmitButton={true}
            showResetButton={false}
            showInfoCard={false}
        />
    );
}
