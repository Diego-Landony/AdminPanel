import React from 'react';

import { EditPageSkeleton } from './EditPageSkeleton';

export function EditUsersSkeleton() {
    return (
        <EditPageSkeleton
            sections={[
                { fields: 2, showIcon: true, showTitle: true }, // Información del Usuario
                { fields: 2, hasCheckboxes: 1, showIcon: true, showTitle: true }, // Cambiar Contraseña
            ]}
            showBackButton={true}
            showSubmitButton={true}
            showResetButton={false}
            showInfoCard={true}
        />
    );
}