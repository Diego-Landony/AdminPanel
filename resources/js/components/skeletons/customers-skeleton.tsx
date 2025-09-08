import { Skeleton } from '@/components/ui/skeleton';

interface CustomersSkeletonProps {
  rows?: number;
}

export function CustomersSkeleton({ rows = 10 }: CustomersSkeletonProps) {
  return (
    <>
      {/* Vista de tabla para desktop */}
      <div className="hidden lg:block">
        <div className="rounded-md border">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b bg-muted/50">
                  <th className="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                    <Skeleton className="h-4 w-16" />
                  </th>
                  <th className="px-4 text-left align-middle font-medium text-muted-foreground">
                    <Skeleton className="h-4 w-20" />
                  </th>
                  <th className="px-4 text-left align-middle font-medium text-muted-foreground">
                    <Skeleton className="h-4 w-16" />
                  </th>
                  <th className="px-4 text-left align-middle font-medium text-muted-foreground">
                    <Skeleton className="h-4 w-24" />
                  </th>
                  <th className="px-4 text-left align-middle font-medium text-muted-foreground">
                    <Skeleton className="h-4 w-20" />
                  </th>
                  <th className="px-4 text-left align-middle font-medium text-muted-foreground">
                    <Skeleton className="h-4 w-28" />
                  </th>
                  <th className="px-4 text-left align-middle font-medium text-muted-foreground">
                    <Skeleton className="h-4 w-16" />
                  </th>
                </tr>
              </thead>
              <tbody>
                {Array.from({ length: rows }).map((_, index) => (
                  <tr key={index} className="border-b hover:bg-muted/50 transition-colors">
                    {/* Nombre y email */}
                    <td className="p-4">
                      <div className="flex items-center gap-3">
                        <Skeleton className="h-10 w-10 rounded-full" />
                        <div className="space-y-1">
                          <Skeleton className="h-4 w-28" />
                          <Skeleton className="h-3 w-36" />
                        </div>
                      </div>
                    </td>
                    
                    {/* Tarjeta y tipo */}
                    <td className="p-4">
                      <div className="space-y-2">
                        <div className="flex items-center gap-2">
                          <Skeleton className="h-4 w-4" />
                          <Skeleton className="h-4 w-24" />
                        </div>
                        <div className="flex items-center gap-2">
                          <Skeleton className="h-3 w-3" />
                          <Skeleton className="h-6 w-16" />
                          <Skeleton className="h-3 w-8" />
                        </div>
                      </div>
                    </td>
                    
                    {/* Estado */}
                    <td className="p-4">
                      <div className="space-y-1">
                        <div className="flex items-center gap-2">
                          <Skeleton className="h-2 w-2 rounded-full" />
                          <Skeleton className="h-6 w-16" />
                        </div>
                        <Skeleton className="h-3 w-20" />
                      </div>
                    </td>
                    
                    {/* Teléfono y ubicación */}
                    <td className="p-4">
                      <div className="space-y-1">
                        <Skeleton className="h-4 w-24" />
                        <div className="flex items-center gap-1">
                          <Skeleton className="h-3 w-3" />
                          <Skeleton className="h-3 w-32" />
                        </div>
                      </div>
                    </td>
                    
                    {/* Última compra */}
                    <td className="p-4">
                      <div className="space-y-1">
                        <Skeleton className="h-4 w-20" />
                        <Skeleton className="h-3 w-16" />
                      </div>
                    </td>
                    
                    {/* Puntos */}
                    <td className="p-4">
                      <div className="space-y-1">
                        <Skeleton className="h-4 w-20" />
                        <Skeleton className="h-3 w-28" />
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Vista de cards para mobile/tablet */}
      <div className="lg:hidden">
        <div className="grid gap-4 sm:gap-4 md:gap-5">
          {Array.from({ length: rows }).map((_, i) => (
            <div key={i} className="space-y-3 rounded-lg border border-border bg-card p-4 sm:p-5">
              {/* Header del card */}
              <div className="flex flex-col space-y-2 sm:flex-row sm:items-start sm:justify-between sm:space-y-0">
                <div className="flex-1 min-w-0 space-y-1">
                  <Skeleton className="h-5 w-3/4" />
                  <Skeleton className="h-3 w-1/2" />
                </div>
                <div className="flex items-center gap-1 flex-shrink-0">
                  <Skeleton className="h-2 w-2 rounded-full" />
                  <Skeleton className="h-6 w-16" />
                </div>
              </div>

              {/* Información básica */}
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1">
                  <div className="flex items-center gap-1">
                    <Skeleton className="h-3 w-3" />
                    <Skeleton className="h-3 w-12" />
                  </div>
                  <Skeleton className="h-6 w-20" />
                </div>
                <div className="space-y-1">
                  <Skeleton className="h-3 w-12" />
                  <Skeleton className="h-4 w-16" />
                </div>
              </div>

              {/* Tipo de cliente */}
              <div className="flex items-center gap-2">
                <Skeleton className="h-4 w-4" />
                <Skeleton className="h-6 w-16" />
                <Skeleton className="h-3 w-8" />
              </div>

              {/* Información de contacto */}
              <div className="space-y-2">
                <div className="flex items-center gap-2">
                  <Skeleton className="h-3 w-4" />
                  <Skeleton className="h-3 w-24" />
                </div>
                <div className="flex items-center gap-2">
                  <Skeleton className="h-3 w-3" />
                  <Skeleton className="h-3 w-32" />
                </div>
              </div>

              {/* Footer */}
              <div className="flex items-center justify-between pt-2 border-t border-border">
                <Skeleton className="h-6 w-16" />
                <div className="flex items-center gap-1">
                  <Skeleton className="h-3 w-3" />
                  <Skeleton className="h-3 w-20" />
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </>
  );
}