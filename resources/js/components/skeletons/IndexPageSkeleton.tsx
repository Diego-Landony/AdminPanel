import { DataTableSkeleton } from './DataTableSkeleton';
import { MobileCardSkeleton } from './MobileCardSkeleton';

interface IndexPageSkeletonProps {
    rows?: number;
    columns?: number;
    breakpoint?: 'sm' | 'md' | 'lg' | 'xl';
    hasAvatar?: boolean;
    hasBadge?: boolean;
    dataFields?: number;
    hasActions?: boolean;
}

export function IndexPageSkeleton({
    rows = 10,
    columns = 5,
    breakpoint = 'lg',
    hasAvatar = true,
    hasBadge = true,
    dataFields = 3,
    hasActions = true
}: IndexPageSkeletonProps) {
    const breakpointClasses = {
        sm: { desktop: 'hidden sm:block', mobile: 'sm:hidden' },
        md: { desktop: 'hidden md:block', mobile: 'md:hidden' },
        lg: { desktop: 'hidden lg:block', mobile: 'lg:hidden' },
        xl: { desktop: 'hidden xl:block', mobile: 'xl:hidden' }
    };

    const { desktop, mobile } = breakpointClasses[breakpoint];

    return (
        <>
            {/* Desktop Table View */}
            <div className={desktop}>
                <DataTableSkeleton
                    rows={rows}
                    columns={columns}
                    hasAvatar={hasAvatar}
                    hasActions={hasActions}
                />
            </div>

            {/* Mobile Card View */}
            <div className={mobile}>
                <MobileCardSkeleton
                    rows={rows}
                    hasIcon={hasAvatar}
                    hasBadge={hasBadge}
                    dataFields={dataFields}
                    hasActions={hasActions}
                />
            </div>
        </>
    );
}