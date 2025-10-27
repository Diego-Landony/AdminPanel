import { IndexPageSkeleton } from './IndexPageSkeleton';

interface CategoriesSkeletonProps {
    rows?: number;
}

export function CategoriesSkeleton({ rows = 5 }: CategoriesSkeletonProps) {
    return <IndexPageSkeleton rows={rows} columns={6} breakpoint="lg" hasAvatar={true} hasBadge={true} dataFields={2} hasActions={true} />;
}
