import { AlertTriangle, RefreshCw } from 'lucide-react';
import React, { Component, ErrorInfo, ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ErrorBoundaryProps {
    children: ReactNode;
    fallback?: ReactNode;
    onError?: (error: Error, errorInfo: ErrorInfo) => void;
    showRetry?: boolean;
    context?: string;
}

interface ErrorBoundaryState {
    hasError: boolean;
    error: Error | null;
    errorInfo: ErrorInfo | null;
    retryCount: number;
}

/**
 * Error Boundary component for graceful error handling
 *
 * Catches JavaScript errors in child components and displays
 * a fallback UI with optional retry functionality.
 */
export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
    private maxRetries = 3;

    constructor(props: ErrorBoundaryProps) {
        super(props);

        this.state = {
            hasError: false,
            error: null,
            errorInfo: null,
            retryCount: 0,
        };
    }

    static getDerivedStateFromError(error: Error): Partial<ErrorBoundaryState> {
        return {
            hasError: true,
            error,
        };
    }

    componentDidCatch(error: Error, errorInfo: ErrorInfo) {
        this.setState({
            error,
            errorInfo,
        });

        // Call optional error callback
        if (this.props.onError) {
            this.props.onError(error, errorInfo);
        }

        // Log error for debugging
        console.error('ErrorBoundary caught an error:', error, errorInfo);
    }

    handleRetry = () => {
        if (this.state.retryCount < this.maxRetries) {
            this.setState(prevState => ({
                hasError: false,
                error: null,
                errorInfo: null,
                retryCount: prevState.retryCount + 1,
            }));
        }
    };

    render() {
        if (this.state.hasError) {
            // Custom fallback UI
            if (this.props.fallback) {
                return this.props.fallback;
            }

            // Default error UI
            const canRetry = this.props.showRetry && this.state.retryCount < this.maxRetries;
            const context = this.props.context || 'componente';

            return (
                <Card className="w-full">
                    <CardHeader className="text-center">
                        <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-destructive/10">
                            <AlertTriangle className="h-6 w-6 text-destructive" />
                        </div>
                        <CardTitle className="text-lg">Error en {context}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 text-center">
                        <p className="text-muted-foreground">
                            Ha ocurrido un error inesperado. Por favor, intenta nuevamente.
                        </p>

                        {process.env.NODE_ENV === 'development' && this.state.error && (
                            <details className="mt-4 text-left">
                                <summary className="cursor-pointer text-sm font-medium text-muted-foreground hover:text-foreground">
                                    Detalles del error (desarrollo)
                                </summary>
                                <pre className="mt-2 overflow-auto rounded bg-muted p-2 text-xs">
                                    {this.state.error.toString()}
                                    {this.state.errorInfo?.componentStack}
                                </pre>
                            </details>
                        )}

                        <div className="flex flex-col gap-2 sm:flex-row sm:justify-center">
                            {canRetry && (
                                <Button onClick={this.handleRetry} variant="default" size="sm">
                                    <RefreshCw className="mr-2 h-4 w-4" />
                                    Reintentar ({this.maxRetries - this.state.retryCount} intentos restantes)
                                </Button>
                            )}

                            <Button
                                onClick={() => window.location.reload()}
                                variant="outline"
                                size="sm"
                            >
                                Recargar página
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            );
        }

        return this.props.children;
    }
}

/**
 * React Hook wrapper for ErrorBoundary
 * Provides a simple way to wrap components with error handling
 */
export const withErrorBoundary = <P extends object>(
    Component: React.ComponentType<P>,
    errorBoundaryProps?: Omit<ErrorBoundaryProps, 'children'>
) => {
    const WrappedComponent = (props: P) => (
        <ErrorBoundary {...errorBoundaryProps}>
            <Component {...props} />
        </ErrorBoundary>
    );

    WrappedComponent.displayName = `withErrorBoundary(${Component.displayName || Component.name})`;

    return WrappedComponent;
};