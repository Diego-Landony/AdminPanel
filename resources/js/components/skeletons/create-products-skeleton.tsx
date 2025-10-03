import { CreatePageSkeleton } from './CreatePageSkeleton';

export function CreateProductsSkeleton() {
    return (
        <CreatePageSkeleton
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
