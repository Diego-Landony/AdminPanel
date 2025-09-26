import { Skeleton } from '@/components/ui/skeleton';

export function HomeSkeleton() {
    return (
        <>
            {/* Desktop */}
            <div className="hidden space-y-4 lg:block">
                <Skeleton className="h-9 w-32" />
                <Skeleton className="h-5 w-48" />
            </div>

            {/* Mobile/Tablet (misma vista) */}
            <div className="space-y-4 lg:hidden">
                <Skeleton className="h-9 w-32" />
                <Skeleton className="h-5 w-48" />
            </div>
        </>
    );
}
