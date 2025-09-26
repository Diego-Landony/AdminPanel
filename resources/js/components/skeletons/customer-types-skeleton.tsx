import { IndexPageSkeleton } from './IndexPageSkeleton';

interface CustomerTypesSkeletonProps {
    rows?: number;
}

export function CustomerTypesSkeleton({ rows = 5 }: CustomerTypesSkeletonProps) {
    return (
        <IndexPageSkeleton
            rows={rows}
            columns={6}
            breakpoint="lg"
            hasAvatar={true}
            hasBadge={true}
            dataFields={3}
            hasActions={true}
        />
    );
}
