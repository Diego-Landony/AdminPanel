import { Skeleton } from '@/components/ui/skeleton';

interface DataTableSkeletonProps {
    rows?: number;
    columns?: number;
    hasAvatar?: boolean;
    hasActions?: boolean;
}

export function DataTableSkeleton({
    rows = 10,
    columns = 5,
    hasAvatar = true,
    hasActions = true
}: DataTableSkeletonProps) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full">
                <thead>
                    <tr className="border-b border-border">
                        {Array.from({ length: columns }).map((_, index) => (
                            <th key={index} className="px-4 py-3 text-left">
                                <Skeleton className="h-4 w-16" />
                            </th>
                        ))}
                        {hasActions && (
                            <th className="px-4 py-3 text-right">
                                <Skeleton className="h-4 w-16" />
                            </th>
                        )}
                    </tr>
                </thead>
                <tbody>
                    {Array.from({ length: rows }).map((_, rowIndex) => (
                        <tr key={rowIndex} className="border-b border-border/50">
                            {/* First column with optional avatar */}
                            <td className="px-4 py-4">
                                {hasAvatar ? (
                                    <div className="flex items-center gap-3">
                                        <Skeleton className="h-10 w-10 rounded-full" />
                                        <div className="space-y-1">
                                            <Skeleton className="h-4 w-24" />
                                            <Skeleton className="h-3 w-32" />
                                        </div>
                                    </div>
                                ) : (
                                    <Skeleton className="h-4 w-24" />
                                )}
                            </td>

                            {/* Additional columns */}
                            {Array.from({ length: columns - 1 }).map((_, colIndex) => (
                                <td key={colIndex} className="px-4 py-4">
                                    {colIndex === 0 ? (
                                        // Second column - more content
                                        <div className="space-y-1">
                                            <Skeleton className="h-4 w-20" />
                                            <Skeleton className="h-3 w-16" />
                                        </div>
                                    ) : colIndex === columns - 2 ? (
                                        // Last content column - badge style
                                        <Skeleton className="h-6 w-16" />
                                    ) : (
                                        // Middle columns - simple text
                                        <Skeleton className="h-4 w-18" />
                                    )}
                                </td>
                            ))}

                            {/* Actions column */}
                            {hasActions && (
                                <td className="px-4 py-4 text-right">
                                    <Skeleton className="h-8 w-8" />
                                </td>
                            )}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}