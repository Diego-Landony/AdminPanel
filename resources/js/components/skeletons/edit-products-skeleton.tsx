import { EditPageSkeleton } from './EditPageSkeleton';

export function EditProductsSkeleton() {
    return (
        <EditPageSkeleton
            sections={[
                {
                    fields: 5,
                },
                {
                    fields: 4,
                },
                {
                    fields: 3,
                },
                {
                    fields: 4,
                },
            ]}
        />
    );
}
