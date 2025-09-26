import { CreatePageSkeleton } from './CreatePageSkeleton';

export function CreateRestaurantsSkeleton() {
    return (
        <CreatePageSkeleton
            sections={[
                {
                    fields: 6, // name, description, address, phone, email, manager_name
                    hasTextarea: true,
                    hasSelect: false,
                    hasCheckboxes: 0
                },
                {
                    fields: 4, // minimum_order_amount, delivery_fee, estimated_delivery_time, sort_order
                    hasTextarea: false,
                    hasSelect: false,
                    hasCheckboxes: 3 // is_active, delivery_active, pickup_active
                },
                {
                    fields: 0, // schedule section - special layout
                    hasTable: true
                }
            ]}
        />
    );
}