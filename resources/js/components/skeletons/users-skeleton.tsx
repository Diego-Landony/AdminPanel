import { Skeleton } from '@/components/ui/skeleton';

interface UsersSkeletonProps {
    rows?: number;
}

export function UsersSkeleton({ rows = 10 }: UsersSkeletonProps) {
    return (
        <>
            {/* Vista de tabla para desktop */}
            <div className="hidden lg:block">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-border">
                                <th className="px-4 py-3 text-left">
                                    <Skeleton className="h-4 w-16" />
                                </th>
                                <th className="px-4 py-3 text-left">
                                    <Skeleton className="h-4 w-12" />
                                </th>
                                <th className="px-4 py-3 text-left">
                                    <Skeleton className="h-4 w-20" />
                                </th>
                                <th className="px-4 py-3 text-left">
                                    <Skeleton className="h-4 w-16" />
                                </th>
                                <th className="px-4 py-3 text-right">
                                    <Skeleton className="h-4 w-16" />
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {Array.from({ length: rows }).map((_, index) => (
                                <tr key={index} className="border-b border-border/50">
                                    <td className="px-4 py-4">
                                        <div className="flex items-center gap-3">
                                            <Skeleton className="h-10 w-10 rounded-full" />
                                            <div className="space-y-1">
                                                <Skeleton className="h-4 w-24" />
                                                <Skeleton className="h-3 w-32" />
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-4">
                                        <Skeleton className="h-6 w-16" />
                                    </td>
                                    <td className="px-4 py-4">
                                        <Skeleton className="h-3 w-20" />
                                    </td>
                                    <td className="px-4 py-4">
                                        <Skeleton className="h-6 w-16" />
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

            {/* Vista de cards para mobile/tablet */}
            <div className="lg:hidden">
                <div className="grid gap-3 md:gap-4">
                    {Array.from({ length: rows }).map((_, index) => (
                        <div key={index} className="space-y-3 rounded-lg border border-border bg-card p-4">
                            <div className="flex items-center justify-between">
                                <div className="flex flex-1 items-center space-x-3">
                                    <Skeleton className="h-8 w-8 rounded-full" />
                                    <div className="space-y-1">
                                        <Skeleton className="h-4 w-20" />
                                        <Skeleton className="h-3 w-28" />
                                    </div>
                                </div>
                                <Skeleton className="h-6 w-16" />
                            </div>
                            <div className="flex gap-1">
                                <Skeleton className="h-5 w-12" />
                                <Skeleton className="h-5 w-16" />
                            </div>
                            <div className="flex justify-between border-t border-border pt-2">
                                <Skeleton className="h-3 w-24" />
                                <Skeleton className="h-3 w-20" />
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
