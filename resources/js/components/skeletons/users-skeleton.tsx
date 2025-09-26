import { IndexPageSkeleton } from './IndexPageSkeleton';

interface UsersSkeletonProps {
    rows?: number;
}

export function UsersSkeleton({ rows = 10 }: UsersSkeletonProps) {
    return (
        <IndexPageSkeleton
            rows={rows}
            columns={5}
            breakpoint="md"
            hasAvatar={true}
            hasBadge={true}
            dataFields={4}
            hasActions={true}
        />
    );
}
