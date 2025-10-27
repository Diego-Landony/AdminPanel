import { CreatePageSkeleton } from './CreatePageSkeleton';

export function CreateCustomerTypesSkeleton() {
    return (
        <CreatePageSkeleton
            sections={[
                {
                    fields: 5, // display_name, name, points_required, multiplier, color, sort_order
                    hasTextarea: false,
                    hasSelect: true,
                    hasCheckboxes: 1, // is_active
                },
            ]}
        />
    );
}
