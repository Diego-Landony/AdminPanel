import { CreatePageSkeleton } from './CreatePageSkeleton';

export function CreateUsersSkeleton() {
    return (
        <CreatePageSkeleton
            sections={[
                {
                    fields: 4, // name, email, password, password_confirmation
                    hasTextarea: false,
                    hasSelect: false,
                    hasCheckboxes: 0
                }
            ]}
        />
    );
}