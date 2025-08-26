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
                <th className="text-left py-3 px-4"><Skeleton className="h-4 w-24" /></th>
                <th className="text-left py-3 px-4"><Skeleton className="h-4 w-48" /></th>
                <th className="text-right py-3 px-4"><Skeleton className="h-4 w-12" /></th>
              </tr>
            </thead>
            <tbody>
              {Array.from({ length: rows }).map((_, index) => (
                <tr key={index} className="border-b border-border/50">
                  <td className="py-4 px-4"><Skeleton className="h-6 w-32" /></td>
                  <td className="py-4 px-4"><Skeleton className="h-4 w-48" /></td>
                  <td className="py-4 px-4 text-right"><Skeleton className="h-8 w-8" /></td>
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
            <div key={index} className="bg-card border border-border rounded-lg p-4 flex items-center justify-between">
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