import { Skeleton } from '@/components/ui/skeleton';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';

export function HomeSkeleton() {
  return (
    <div className="space-y-6">
      {/* Header skeleton */}
      <div className="space-y-2">
        <Skeleton className="h-9 w-32" />
        <Skeleton className="h-5 w-48" />
      </div>

      {/* Main content skeleton with pattern */}
      <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
        <div className="absolute inset-0 flex items-center justify-center">
          <div className="space-y-4 text-center">
            <Skeleton className="h-8 w-32 mx-auto" />
            <Skeleton className="h-4 w-48 mx-auto" />
          </div>
        </div>
      </div>
    </div>
  );
}
