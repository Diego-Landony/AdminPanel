import { IndexPageSkeleton } from './IndexPageSkeleton';

interface ProductsSkeletonProps {
    rows?: number;
}

export function ProductsSkeleton({ rows = 5 }: ProductsSkeletonProps) {
    return (
        <IndexPageSkeleton
            rows={rows}
            columns={7}
            breakpoint="lg"
            hasAvatar={true}
            hasBadge={true}
            dataFields={3}
            hasActions={true}
        />
    );
}
