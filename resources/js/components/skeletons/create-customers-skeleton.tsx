import { CreatePageSkeleton } from './CreatePageSkeleton';

export function CreateCustomersSkeleton() {
    return (
        <CreatePageSkeleton
            sections={[
                {
                    fields: 6, // full_name, email, subway_card, birth_date, gender, customer_type
                    hasTextarea: false,
                    hasSelect: true,
                    hasCheckboxes: 0
                },
                {
                    fields: 2, // phone, location
                    hasTextarea: false,
                    hasSelect: false,
                    hasCheckboxes: 0
                },
                {
                    fields: 2, // password, password_confirmation
                    hasTextarea: false,
                    hasSelect: false,
                    hasCheckboxes: 1 // email_verified_at
                }
            ]}
        />
    );
}