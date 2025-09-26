import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { router } from '@inertiajs/react';

interface PaginatedData {
    current_page: number;
    last_page: number;
    from?: number;
    to?: number;
    total?: number;
}

interface PaginationWrapperProps {
    data: PaginatedData;
    routeName: string;
    filters?: Record<string, unknown>;
    className?: string;
    showInfo?: boolean;
}

export function PaginationWrapper({ data, routeName, filters = {}, className = 'mt-6', showInfo = false }: PaginationWrapperProps) {
    const goToPage = (page: number) => {
        router.get(
            routeName,
            {
                page,
                ...filters,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    // No mostrar paginación si solo hay una página
    if (data.last_page <= 1) {
        return null;
    }

    return (
        <div className={className}>
            <Pagination>
                <PaginationContent>
                    <PaginationItem>
                        <PaginationPrevious
                            href="#"
                            onClick={(e) => {
                                e.preventDefault();
                                goToPage(data.current_page - 1);
                            }}
                            className={data.current_page <= 1 ? 'pointer-events-none opacity-50' : ''}
                        />
                    </PaginationItem>

                    {/* Primera página */}
                    {data.current_page > 3 && (
                        <>
                            <PaginationItem>
                                <PaginationLink
                                    href="#"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        goToPage(1);
                                    }}
                                    className="max-w-[4rem] min-w-[2.5rem] overflow-hidden text-ellipsis"
                                >
                                    <span className="tabular-nums">1</span>
                                </PaginationLink>
                            </PaginationItem>
                            {data.current_page > 4 && (
                                <PaginationItem>
                                    <PaginationEllipsis />
                                </PaginationItem>
                            )}
                        </>
                    )}

                    {/* Páginas alrededor de la actual */}
                    {Array.from({ length: Math.min(3, data.last_page) }, (_, i) => {
                        const page = data.current_page - 1 + i;
                        if (page < 1 || page > data.last_page) return null;

                        return (
                            <PaginationItem key={page}>
                                <PaginationLink
                                    href="#"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        goToPage(page);
                                    }}
                                    isActive={page === data.current_page}
                                    className="max-w-[4rem] min-w-[2.5rem] overflow-hidden text-ellipsis"
                                >
                                    <span className="tabular-nums">{page}</span>
                                </PaginationLink>
                            </PaginationItem>
                        );
                    })}

                    {/* Última página */}
                    {data.current_page < data.last_page - 2 && (
                        <>
                            {data.current_page < data.last_page - 3 && (
                                <PaginationItem>
                                    <PaginationEllipsis />
                                </PaginationItem>
                            )}
                            <PaginationItem>
                                <PaginationLink
                                    href="#"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        goToPage(data.last_page);
                                    }}
                                    className="max-w-[4rem] min-w-[2.5rem] overflow-hidden text-ellipsis"
                                >
                                    <span className="tabular-nums">{data.last_page}</span>
                                </PaginationLink>
                            </PaginationItem>
                        </>
                    )}

                    <PaginationItem>
                        <PaginationNext
                            href="#"
                            onClick={(e) => {
                                e.preventDefault();
                                goToPage(data.current_page + 1);
                            }}
                            className={data.current_page >= data.last_page ? 'pointer-events-none opacity-50' : ''}
                        />
                    </PaginationItem>
                </PaginationContent>
            </Pagination>

            {/* Información opcional de paginación */}
            {showInfo && data.from && data.to && data.total && (
                <div className="mt-4 text-center text-sm text-muted-foreground">
                    <span className="inline-block max-w-full overflow-hidden break-words text-ellipsis">
                        <span className="tabular-nums">
                            Página {data.current_page} de {data.last_page}
                        </span>
                        {' - '}
                        <span className="tabular-nums">
                            Mostrando {data.from} a {data.to} de {data.total} elementos
                        </span>
                    </span>
                </div>
            )}
        </div>
    );
}
