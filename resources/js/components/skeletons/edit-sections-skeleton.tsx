import { EditPageSkeleton } from './EditPageSkeleton';

export function EditSectionsSkeleton() {
    return (
        <EditPageSkeleton
            sections={[
                {
                    fields: 6,
                },
                {
                    fields: 3,
                },
            ]}
        />
    );
}
