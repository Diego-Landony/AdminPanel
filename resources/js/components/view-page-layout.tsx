import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import React from 'react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';

interface ViewPageLayoutProps {
    title: string;
    description: string;
    backHref: string;
    backLabel?: string;
    pageTitle?: string;
    children: React.ReactNode;
    loading?: boolean;
    loadingSkeleton?: React.ComponentType;
    actions?: React.ReactNode;
}

export function ViewPageLayout({
    title,
    description,
    backHref,
    backLabel = 'Volver',
    pageTitle,
    children,
    loading = false,
    loadingSkeleton: LoadingSkeleton,
    actions,
}: ViewPageLayoutProps) {
    const finalPageTitle = pageTitle || title;

    return (
        <AppLayout>
            <Head title={finalPageTitle} />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                {loading && LoadingSkeleton ? (
                    <LoadingSkeleton />
                ) : (
                    <>
                        {/* Header */}
                        <div className="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                            <div className="min-w-0 flex-1">
                                <h1 className="truncate text-2xl font-bold tracking-tight lg:text-3xl">{title}</h1>
                                <p className="break-words text-muted-foreground">{description}</p>
                            </div>
                            <div className="flex items-center gap-3">
                                {actions}
                                <Link href={backHref} className="w-full flex-shrink-0 sm:w-auto">
                                    <Button variant="outline" className="w-full sm:w-auto">
                                        <ArrowLeft className="mr-2 h-4 w-4 flex-shrink-0" />
                                        <span className="truncate">{backLabel}</span>
                                    </Button>
                                </Link>
                            </div>
                        </div>

                        {/* Content */}
                        <div className="mx-auto w-full max-w-6xl min-w-0">{children}</div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}