import { Skeleton } from '@/components/ui/skeleton';

interface ActivitySkeletonProps {
    rows?: number;
}

export function ActivitySkeleton({ rows = 10 }: ActivitySkeletonProps) {
    return (
        <>
            {/* Desktop: tabla ligera */}
            <div className="hidden lg:block">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-border">
                                <th className="px-4 py-3 text-left">
                                    <Skeleton className="h-4 w-12" />
                                </th>
                                <th className="px-4 py-3 text-left">
                                    <Skeleton className="h-4 w-48" />
                                </th>
                                <th className="px-4 py-3 text-right">
                                    <Skeleton className="h-4 w-20" />
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {Array.from({ length: rows }).map((_, index) => (
                                <tr key={index} className="border-b border-border/50">
                                    <td className="px-4 py-4">
                                        <Skeleton className="h-10 w-10 rounded-full" />
                                    </td>
                                    <td className="px-4 py-4">
                                        <Skeleton className="h-4 w-3/4" />
                                    </td>
                                    <td className="px-4 py-4 text-right">
                                        <Skeleton className="h-6 w-20" />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Mobile/Tablet: cards (misma vista) */}
            <div className="lg:hidden">
                <div className="space-y-3">
                    {Array.from({ length: rows }).map((_, index) => (
                        <div key={index} className="flex items-center justify-between rounded-lg border border-border bg-card p-4">
                            <div className="flex items-center space-x-4">
                                <Skeleton className="h-12 w-12 rounded-full" />
                                <div className="space-y-1">
                                    <Skeleton className="h-4 w-40" />
                                    <Skeleton className="h-3 w-32" />
                                </div>
                            </div>
                            <Skeleton className="h-6 w-20" />
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
