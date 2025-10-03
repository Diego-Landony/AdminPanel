import { CreatePageSkeleton } from './CreatePageSkeleton';

export function CreateSectionsSkeleton() {
    return (
        <CreatePageSkeleton
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
