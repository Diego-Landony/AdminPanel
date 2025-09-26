import { IndexPageSkeleton } from './IndexPageSkeleton';

interface RolesSkeletonProps {
    rows?: number;
}

export function RolesSkeleton({ rows = 10 }: RolesSkeletonProps) {
    return (
        <IndexPageSkeleton
            rows={rows}
            columns={5}
            breakpoint="md"
            hasAvatar={true}
            hasBadge={true}
            dataFields={3}
            hasActions={true}
        />
    );
}
