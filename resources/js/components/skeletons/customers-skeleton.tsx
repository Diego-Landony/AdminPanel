import { IndexPageSkeleton } from './IndexPageSkeleton';

interface CustomersSkeletonProps {
    rows?: number;
}

export function CustomersSkeleton({ rows = 10 }: CustomersSkeletonProps) {
    return <IndexPageSkeleton rows={rows} columns={7} breakpoint="md" hasAvatar={true} hasBadge={true} dataFields={6} hasActions={true} />;
}
