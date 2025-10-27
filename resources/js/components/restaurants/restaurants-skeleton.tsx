import { IndexPageSkeleton } from '../skeletons/IndexPageSkeleton';

interface RestaurantsSkeletonProps {
    rows?: number;
}

export function RestaurantsSkeleton({ rows = 10 }: RestaurantsSkeletonProps) {
    return <IndexPageSkeleton rows={rows} columns={6} breakpoint="md" hasAvatar={true} hasBadge={true} dataFields={5} hasActions={true} />;
}
