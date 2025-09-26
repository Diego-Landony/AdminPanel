import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import React from 'react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';

interface CreatePageLayoutProps {
    title: string;
    description: string;
    backHref: string;
    backLabel: string;
    onSubmit: (e: React.FormEvent) => void;
    submitLabel: string;
    processing: boolean;
    cancelHref?: string;
    pageTitle?: string;
    children: React.ReactNode;
}

export function CreatePageLayout({
    title,
    description,
    backHref,
    backLabel,
    onSubmit,
    submitLabel,
    processing,
    cancelHref,
    pageTitle,
    children,
}: CreatePageLayoutProps) {
    const finalCancelHref = cancelHref || backHref;
    const finalPageTitle = pageTitle || title;

    return (
        <AppLayout>
            <Head title={finalPageTitle} />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                {/* Encabezado */}
                <div className="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                    <div className="min-w-0 flex-1">
                        <h1 className="truncate text-2xl font-bold tracking-tight lg:text-3xl">{title}</h1>
                        <p className="break-words text-muted-foreground">{description}</p>
                    </div>
                    <Link href={backHref} className="w-full flex-shrink-0 sm:w-auto">
                        <Button variant="outline" className="w-full sm:w-auto">
                            <ArrowLeft className="mr-2 h-4 w-4 flex-shrink-0" />
                            <span className="truncate">{backLabel}</span>
                        </Button>
                    </Link>
                </div>

                <form onSubmit={onSubmit} className="space-y-6">
                    <div className="mx-auto w-full max-w-2xl min-w-0 px-1">{children}</div>

                    {/* Botones de Acción */}
                    <div className="mt-8 flex flex-col items-stretch justify-end gap-3 px-1 sm:flex-row sm:items-center sm:gap-4">
                        <Link href={finalCancelHref} className="w-full sm:w-auto">
                            <Button variant="outline" type="button" className="w-full sm:w-auto">
                                Cancelar
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                            <Save className="mr-2 h-4 w-4 flex-shrink-0" />
                            <span className="truncate">{processing ? 'Guardando...' : submitLabel}</span>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
