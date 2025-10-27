import { CreatePageSkeleton } from './CreatePageSkeleton';

export function CreateRolesSkeleton() {
    return (
        <CreatePageSkeleton
            sections={[
                {
                    fields: 2, // name, description
                    hasTextarea: true,
                    hasSelect: false,
                    hasCheckboxes: 0,
                },
                {
                    fields: 0, // permissions section has table
                    hasTable: true,
                },
            ]}
        />
    );
}
