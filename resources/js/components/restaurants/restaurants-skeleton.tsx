import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

export function RestaurantsSkeleton() {
    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div className="space-y-2">
                    <Skeleton className="h-8 w-64" />
                    <Skeleton className="h-4 w-80" />
                </div>
                <Skeleton className="h-10 w-40" />
            </div>

            {/* Stats Cards */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {Array.from({ length: 4 }).map((_, i) => (
                    <Card key={i}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <Skeleton className="h-4 w-32" />
                            <Skeleton className="h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <Skeleton className="h-8 w-12" />
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Filters */}
            <div className="flex items-center justify-between">
                <Skeleton className="h-10 w-64" />
                <Skeleton className="h-10 w-20" />
            </div>

            {/* Desktop Table Skeleton */}
            <div className="hidden lg:block">
                <div className="rounded-md border">
                    <div className="border-b p-4">
                        <div className="grid grid-cols-9 gap-4">
                            <Skeleton className="h-4 w-16" />
                            <Skeleton className="h-4 w-20" />
                            <Skeleton className="h-4 w-16" />
                            <Skeleton className="h-4 w-14" />
                            <Skeleton className="h-4 w-20" />
                            <Skeleton className="h-4 w-16" />
                            <Skeleton className="h-4 w-12" />
                            <Skeleton className="h-4 w-12" />
                            <Skeleton className="h-4 w-16" />
                        </div>
                    </div>
                    {Array.from({ length: 10 }).map((_, i) => (
                        <div key={i} className="border-b p-4 last:border-b-0">
                            <div className="grid grid-cols-9 items-center gap-4">
                                <div className="space-y-1">
                                    <Skeleton className="h-4 w-32" />
                                    <Skeleton className="h-3 w-20" />
                                </div>
                                <Skeleton className="h-4 w-40" />
                                <div className="flex items-center space-x-1">
                                    <Skeleton className="h-3 w-3" />
                                    <Skeleton className="h-4 w-24" />
                                </div>
                                <Skeleton className="h-6 w-20" />
                                <div className="flex items-center space-x-1">
                                    <Skeleton className="h-3 w-3" />
                                    <Skeleton className="h-4 w-16" />
                                </div>
                                <div className="flex items-center space-x-1">
                                    <Skeleton className="h-3 w-3" />
                                    <Skeleton className="h-4 w-20" />
                                </div>
                                <div className="flex items-center space-x-1">
                                    {Array.from({ length: 5 }).map((_, j) => (
                                        <Skeleton key={j} className="h-3 w-3" />
                                    ))}
                                    <Skeleton className="h-4 w-8" />
                                </div>
                                <Skeleton className="h-4 w-6" />
                                <Skeleton className="ml-auto h-8 w-8" />
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Mobile/Tablet Cards Skeleton */}
            <div className="space-y-4 lg:hidden">
                {Array.from({ length: 5 }).map((_, i) => (
                    <Card key={i}>
                        <CardHeader>
                            <div className="flex items-start justify-between">
                                <div className="space-y-2">
                                    <Skeleton className="h-6 w-48" />
                                    <Skeleton className="h-4 w-24" />
                                </div>
                                <Skeleton className="h-8 w-8" />
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Skeleton className="h-4 w-full" />

                            <div className="space-y-3">
                                {Array.from({ length: 4 }).map((_, j) => (
                                    <div key={j} className="flex items-center space-x-2">
                                        <Skeleton className="h-4 w-4" />
                                        <Skeleton className="h-4 w-32" />
                                    </div>
                                ))}
                            </div>

                            <div className="flex items-center justify-between">
                                <Skeleton className="h-6 w-24" />
                                <Skeleton className="h-4 w-16" />
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Pagination */}
            <div className="flex items-center justify-between">
                <Skeleton className="h-4 w-48" />
                <div className="flex items-center space-x-2">
                    <Skeleton className="h-8 w-8" />
                    <Skeleton className="h-8 w-8" />
                    <Skeleton className="h-8 w-8" />
                    <Skeleton className="h-8 w-8" />
                    <Skeleton className="h-8 w-8" />
                </div>
            </div>
        </div>
    );
}
