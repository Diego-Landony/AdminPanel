import { IndexPageSkeleton } from './IndexPageSkeleton';

interface SectionsSkeletonProps {
    rows?: number;
}

export function SectionsSkeleton({ rows = 5 }: SectionsSkeletonProps) {
    return (
        <IndexPageSkeleton
            rows={rows}
            columns={7}
            breakpoint="lg"
            hasAvatar={false}
            hasBadge={true}
            dataFields={3}
            hasActions={true}
        />
    );
}
