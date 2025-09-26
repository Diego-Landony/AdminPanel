import { Skeleton } from '@/components/ui/skeleton';

interface MobileCardSkeletonProps {
    rows?: number;
    hasIcon?: boolean;
    hasBadge?: boolean;
    dataFields?: number;
    hasActions?: boolean;
}

export function MobileCardSkeleton({
    rows = 5,
    hasIcon = true,
    hasBadge = true,
    dataFields = 3,
    hasActions = true
}: MobileCardSkeletonProps) {
    return (
        <div className="space-y-3">
            {Array.from({ length: rows }).map((_, index) => (
                <div key={index} className="space-y-3 rounded-lg border border-border bg-card p-4">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div className="flex flex-1 items-center space-x-3">
                            {hasIcon && <Skeleton className="h-8 w-8 rounded-full" />}
                            <div className="space-y-1">
                                <Skeleton className="h-4 w-24" />
                                <Skeleton className="h-3 w-32" />
                            </div>
                        </div>
                        {hasBadge && <Skeleton className="h-6 w-16" />}
                    </div>

                    {/* Data fields */}
                    {dataFields > 0 && (
                        <div className="space-y-2">
                            {Array.from({ length: dataFields }).map((_, fieldIndex) => (
                                <div key={fieldIndex} className="flex items-center justify-between">
                                    <Skeleton className="h-3 w-16" />
                                    <Skeleton className="h-4 w-20" />
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Actions footer */}
                    {hasActions && (
                        <div className="flex items-center justify-between border-t border-border pt-2">
                            <Skeleton className="h-3 w-24" />
                            <Skeleton className="h-8 w-8" />
                        </div>
                    )}
                </div>
            ))}
        </div>
    );
}