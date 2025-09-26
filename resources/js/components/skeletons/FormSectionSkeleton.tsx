import { Skeleton } from '@/components/ui/skeleton';

interface FormSectionSkeletonProps {
    fields?: number;
    hasTextarea?: boolean;
    hasSelect?: boolean;
    hasCheckboxes?: number;
    hasTable?: boolean;
}

export function FormSectionSkeleton({
    fields = 3,
    hasTextarea = false,
    hasSelect = false,
    hasCheckboxes = 0,
    hasTable = false
}: FormSectionSkeletonProps) {
    return (
        <div className="space-y-6 rounded-lg border border-border bg-card p-6 animate-pulse">
            {/* Section Header */}
            <div className="flex items-center gap-3 border-b border-border pb-4">
                <Skeleton className="h-5 w-5 rounded-full" />
                <div className="space-y-1">
                    <Skeleton className="h-5 w-32 rounded-md" />
                    <Skeleton className="h-3 w-48 rounded-sm" />
                </div>
            </div>

            {/* Form Fields */}
            <div className="space-y-4">
                {Array.from({ length: fields }).map((_, index) => (
                    <div key={index} className="space-y-2">
                        <Skeleton className="h-4 w-24" />
                        {index === 0 && hasTextarea ? (
                            <Skeleton className="h-24 w-full" />
                        ) : index === 1 && hasSelect ? (
                            <Skeleton className="h-10 w-full" />
                        ) : (
                            <Skeleton className="h-10 w-full" />
                        )}
                    </div>
                ))}

                {/* Checkboxes if any */}
                {hasCheckboxes > 0 && (
                    <div className="space-y-3">
                        {Array.from({ length: hasCheckboxes }).map((_, index) => (
                            <div key={index} className="flex items-center space-x-2">
                                <Skeleton className="h-4 w-4" />
                                <Skeleton className="h-4 w-24" />
                            </div>
                        ))}
                    </div>
                )}

                {/* Table if any */}
                {hasTable && (
                    <div className="space-y-3">
                        <Skeleton className="h-8 w-full" />
                        {Array.from({ length: 4 }).map((_, index) => (
                            <div key={index} className="flex items-center justify-between">
                                <Skeleton className="h-4 w-24" />
                                <div className="flex space-x-4">
                                    <Skeleton className="h-4 w-4" />
                                    <Skeleton className="h-4 w-4" />
                                    <Skeleton className="h-4 w-4" />
                                    <Skeleton className="h-4 w-4" />
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}