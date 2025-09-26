import React from 'react';

import { EditPageSkeleton } from './EditPageSkeleton';

export function EditCustomersSkeleton() {
    return (
        <EditPageSkeleton
            sections={[
                { fields: 6, showIcon: true, showTitle: true }, // Información Personal
                { fields: 3, hasTextarea: true, showIcon: true, showTitle: true }, // Información de Contacto
                { fields: 2, showIcon: true, showTitle: true }, // Seguridad
            ]}
            showBackButton={true}
            showSubmitButton={true}
            showResetButton={true}
            showInfoCard={true}
        />
    );
}