import { Skeleton } from '@/components/ui/skeleton';

interface CustomerTypesSkeletonProps {
  rows?: number;
}

export function CustomerTypesSkeleton({ rows = 5 }: CustomerTypesSkeletonProps) {
  return (
    <>
      {/* Desktop: tabla */}
      <div className="hidden lg:block">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-border">
                <th className="text-left py-3 px-4"><Skeleton className="h-4 w-16" /></th>
                <th className="text-left py-3 px-4"><Skeleton className="h-4 w-24" /></th>
                <th className="text-left py-3 px-4"><Skeleton className="h-4 w-20" /></th>
                <th className="text-left py-3 px-4"><Skeleton className="h-4 w-16" /></th>
                <th className="text-left py-3 px-4"><Skeleton className="h-4 w-20" /></th>
                <th className="text-right py-3 px-4"><Skeleton className="h-4 w-12" /></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border/50">
              {Array.from({ length: rows }).map((_, index) => (
                <tr key={index} className="hover:bg-muted/30 transition-colors">
                  <td className="py-4 px-4">
                    <div className="flex items-center gap-3">
                      <Skeleton className="h-10 w-10 rounded-full" />
                      <div className="space-y-1">
                        <Skeleton className="h-4 w-24" />
                        <Skeleton className="h-3 w-20" />
                      </div>
                    </div>
                  </td>
                  <td className="py-4 px-4">
                    <div className="space-y-1">
                      <Skeleton className="h-4 w-16" />
                      <Skeleton className="h-3 w-12" />
                    </div>
                  </td>
                  <td className="py-4 px-4">
                    <Skeleton className="h-4 w-12" />
                  </td>
                  <td className="py-4 px-4">
                    <Skeleton className="h-6 w-16" />
                  </td>
                  <td className="py-4 px-4">
                    <Skeleton className="h-3 w-20" />
                  </td>
                  <td className="py-4 px-4 text-right">
                    <Skeleton className="h-8 w-8" />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Mobile/Tablet: cards verticales */}
      <div className="lg:hidden">
        <div className="space-y-3 md:space-y-4">
          {Array.from({ length: rows }).map((_, index) => (
            <div key={index} className="rounded-lg border border-border bg-card p-4 space-y-3">
              {/* Header */}
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Skeleton className="h-8 w-8 rounded-full" />
                  <div className="space-y-1">
                    <Skeleton className="h-5 w-24" />
                    <Skeleton className="h-3 w-20" />
                  </div>
                </div>
                <Skeleton className="h-6 w-16 rounded-full" />
              </div>
              
              {/* Grid info en mobile */}
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1">
                  <Skeleton className="h-3 w-16" />
                  <Skeleton className="h-4 w-12" />
                </div>
                <div className="space-y-1">
                  <Skeleton className="h-3 w-20" />
                  <Skeleton className="h-4 w-16" />
                </div>
              </div>
              
              {/* Footer mobile */}
              <div className="flex items-center justify-between pt-2 border-t border-border">
                <Skeleton className="h-3 w-28" />
                <Skeleton className="h-8 w-8" />
              </div>
            </div>
          ))}
        </div>
      </div>
    </>
  );
}