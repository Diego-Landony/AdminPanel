import { Skeleton } from '@/components/ui/skeleton';

interface RolesSkeletonProps {
    rows?: number;
}

export function RolesSkeleton({ rows = 10 }: RolesSkeletonProps) {
    return (
        <>
            {/* Desktop: tabla simple */}
            <div className="hidden lg:block">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-border">
                                <th className="px-4 py-3 text-left">
                                    <Skeleton className="h-4 w-24" />
                                </th>
                                <th className="px-4 py-3 text-left">
                                    <Skeleton className="h-4 w-48" />
                                </th>
                                <th className="px-4 py-3 text-right">
                                    <Skeleton className="h-4 w-12" />
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {Array.from({ length: rows }).map((_, index) => (
                                <tr key={index} className="border-b border-border/50">
                                    <td className="px-4 py-4">
                                        <Skeleton className="h-6 w-32" />
                                    </td>
                                    <td className="px-4 py-4">
                                        <Skeleton className="h-4 w-48" />
                                    </td>
                                    <td className="px-4 py-4 text-right">
                                        <Skeleton className="h-8 w-8" />
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
                            <div className="flex-1 space-y-1">
                                <Skeleton className="h-5 w-36" />
                                <Skeleton className="h-3 w-48" />
                            </div>
                            <Skeleton className="h-8 w-8" />
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
