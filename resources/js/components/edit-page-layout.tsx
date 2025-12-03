import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import React from 'react';

import { Button } from '@/components/ui/button';
import { LoadingSpinner } from '@/components/ui/loading-spinner';
import AppLayout from '@/layouts/app-layout';

interface EditPageLayoutProps {
    title: string;
    description?: string;
    backHref: string;
    backLabel?: string;
    onSubmit: (e: React.FormEvent) => void;
    submitLabel?: string;
    processing: boolean;
    disabled?: boolean;
    cancelHref?: string;
    pageTitle?: string;
    children: React.ReactNode;
    loading?: boolean;
    loadingSkeleton?: React.ComponentType;
    isDirty?: boolean;
    onReset?: () => void;
    showResetButton?: boolean;
}

export function EditPageLayout({
    title,
    description,
    backHref,
    backLabel = 'Volver',
    onSubmit,
    submitLabel = 'Actualizar',
    processing,
    disabled = false,
    cancelHref,
    pageTitle,
    children,
    loading = false,
    loadingSkeleton: LoadingSkeleton,
    isDirty = false,
    onReset,
    showResetButton = false,
}: EditPageLayoutProps) {
    const finalCancelHref = cancelHref || backHref;
    const finalPageTitle = pageTitle || title;
    const isSubmitDisabled = processing || disabled || (!isDirty && showResetButton);

    return (
        <AppLayout>
            <Head title={finalPageTitle} />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                {loading && LoadingSkeleton ? (
                    <LoadingSkeleton />
                ) : (
                    <>
                        {/* Encabezado */}
                        <div className="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                            <div className="min-w-0 flex-1">
                                <h1 className="truncate text-2xl font-bold tracking-tight lg:text-3xl">{title}</h1>
                                {description && <p className="break-words text-muted-foreground">{description}</p>}
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
                                {/* Indicador de estado si se usa isDirty */}
                                {showResetButton && (
                                    <div className="hidden text-sm text-muted-foreground sm:block">
                                        {isDirty ? 'Tienes cambios sin guardar' : 'Sin cambios'}
                                    </div>
                                )}

                                {/* Botón de resetear cambios (opcional) */}
                                {showResetButton && onReset && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={onReset}
                                        disabled={processing || !isDirty}
                                        className="w-full sm:w-auto"
                                    >
                                        Descartar Cambios
                                    </Button>
                                )}

                                <Link href={finalCancelHref} className="w-full sm:w-auto">
                                    <Button variant="outline" type="button" className="w-full sm:w-auto">
                                        Cancelar
                                    </Button>
                                </Link>

                                <Button
                                    type="submit"
                                    disabled={isSubmitDisabled}
                                    className={`w-full sm:w-auto ${processing ? 'cursor-not-allowed' : ''}`}
                                >
                                    {processing ? (
                                        <LoadingSpinner size="sm" variant="white" className="mr-2 flex-shrink-0" />
                                    ) : (
                                        <Save className="mr-2 h-4 w-4 flex-shrink-0" />
                                    )}
                                    <span className="truncate">{processing ? 'Guardando...' : disabled ? 'No Editable' : submitLabel}</span>
                                </Button>
                            </div>
                        </form>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
