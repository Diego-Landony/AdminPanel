import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import { useBreadcrumbs } from '@/hooks/useBreadcrumbs';

export function Breadcrumbs({ breadcrumbs: manualBreadcrumbs }: { breadcrumbs?: BreadcrumbItemType[] }) {
    // Si se proporcionan breadcrumbs manuales y no están vacíos, usarlos; sino, generar automáticamente
    const hasManualBreadcrumbs = manualBreadcrumbs && manualBreadcrumbs.length > 0;

    const autoBreadcrumbs = useBreadcrumbs({
        override: hasManualBreadcrumbs ? manualBreadcrumbs : undefined,
        autoGenerate: !hasManualBreadcrumbs
    });

    const breadcrumbs = hasManualBreadcrumbs ? manualBreadcrumbs : autoBreadcrumbs;

    return (
        <>
            {breadcrumbs.length > 0 && (
                <Breadcrumb>
                    <BreadcrumbList>
                        {breadcrumbs.map((item, index) => {
                            const isLast = index === breadcrumbs.length - 1;
                            return (
                                <Fragment key={index}>
                                    <BreadcrumbItem>
                                        {isLast ? (
                                            <BreadcrumbPage>{item.title}</BreadcrumbPage>
                                        ) : (
                                            <BreadcrumbLink asChild>
                                                <Link href={item.href}>{item.title}</Link>
                                            </BreadcrumbLink>
                                        )}
                                    </BreadcrumbItem>
                                    {!isLast && <BreadcrumbSeparator />}
                                </Fragment>
                            );
                        })}
                    </BreadcrumbList>
                </Breadcrumb>
            )}
        </>
    );
}
