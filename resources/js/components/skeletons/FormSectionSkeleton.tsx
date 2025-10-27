import { Skeleton } from '@/components/ui/skeleton';

export interface FormSectionConfig {
    fields: number;
    hasTextarea?: boolean;
    hasSelect?: boolean;
    hasCheckboxes?: number;
    hasTable?: boolean;
    showIcon?: boolean;
    showTitle?: boolean;
}

interface FormSectionSkeletonProps {
    fields?: number;
    hasTextarea?: boolean;
    hasSelect?: boolean;
    hasCheckboxes?: number;
    hasTable?: boolean;
    showIcon?: boolean;
    showTitle?: boolean;
}

export function FormSectionSkeleton({
    fields = 3,
    hasTextarea = false,
    hasSelect = false,
    hasCheckboxes = 0,
    hasTable = false,
    showIcon = true,
    showTitle = true,
}: FormSectionSkeletonProps) {
    return (
        <div className="animate-pulse space-y-6 rounded-lg border border-border bg-card p-6">
            {/* Section Header */}
            {(showIcon || showTitle) && (
                <div className="flex items-center gap-3 border-b border-border pb-4">
                    {showIcon && <Skeleton className="h-5 w-5 rounded-full" />}
                    {showTitle && (
                        <div className="space-y-1">
                            <Skeleton className="h-5 w-32 rounded-md" />
                            <Skeleton className="h-3 w-48 rounded-sm" />
                        </div>
                    )}
                </div>
            )}

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
