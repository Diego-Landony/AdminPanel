import { AlertCircle, RefreshCw, Wifi, WifiOff } from 'lucide-react';
import React, { useCallback, useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { LoadingSpinner } from '@/components/ui/loading-spinner';
import { NOTIFICATIONS } from '@/constants/ui-constants';

interface NetworkErrorRetryProps {
    /** Error message to display */
    error?: string;
    /** Function to call when retry is clicked */
    onRetry: () => void;
    /** Whether retry is currently in progress */
    isRetrying?: boolean;
    /** Maximum number of retry attempts */
    maxRetries?: number;
    /** Current retry count */
    retryCount?: number;
    /** Whether to show connection status */
    showConnectionStatus?: boolean;
    /** Custom title */
    title?: string;
    /** Custom description */
    description?: string;
    /** Size variant */
    size?: 'sm' | 'md' | 'lg';
}

/**
 * Network error component with retry functionality
 *
 * Provides a user-friendly interface for handling network errors
 * with automatic retry capabilities and connection status.
 */
export const NetworkErrorRetry: React.FC<NetworkErrorRetryProps> = ({
    error,
    onRetry,
    isRetrying = false,
    maxRetries = 3,
    retryCount = 0,
    showConnectionStatus = true,
    title = 'Error de conexión',
    description,
    size = 'md',
}) => {
    const [isOnline, setIsOnline] = useState(navigator.onLine);

    // Monitor network status
    useEffect(() => {
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    const handleRetry = useCallback(() => {
        if (!isRetrying && retryCount < maxRetries) {
            onRetry();
        }
    }, [isRetrying, retryCount, maxRetries, onRetry]);

    const getErrorMessage = () => {
        if (error) return error;
        if (!isOnline) return NOTIFICATIONS.error.networkConnection;
        return NOTIFICATIONS.error.dataLoading;
    };

    const getRecommendation = () => {
        if (!isOnline) {
            return 'Verifica tu conexión a internet e intenta nuevamente.';
        }
        if (retryCount >= maxRetries) {
            return 'Si el problema persiste, contacta al soporte técnico.';
        }
        return 'Intenta recargar los datos o verifica tu conexión.';
    };

    const canRetry = retryCount < maxRetries && !isRetrying;

    // Size variants
    const sizeClasses = {
        sm: {
            icon: 'h-8 w-8',
            iconContainer: 'h-16 w-16',
            title: 'text-base',
            spacing: 'space-y-2',
        },
        md: {
            icon: 'h-6 w-6',
            iconContainer: 'h-12 w-12',
            title: 'text-lg',
            spacing: 'space-y-4',
        },
        lg: {
            icon: 'h-8 w-8',
            iconContainer: 'h-16 w-16',
            title: 'text-xl',
            spacing: 'space-y-6',
        },
    };

    const classes = sizeClasses[size];

    return (
        <Card className="w-full">
            <CardHeader className="text-center">
                <div className={`mx-auto mb-4 flex ${classes.iconContainer} items-center justify-center rounded-full bg-destructive/10`}>
                    <AlertCircle className={`${classes.icon} text-destructive`} />
                </div>
                <CardTitle className={classes.title}>{title}</CardTitle>
            </CardHeader>
            <CardContent className={`${classes.spacing} text-center`}>
                <p className="text-muted-foreground">{description || getErrorMessage()}</p>

                <p className="text-sm text-muted-foreground">{getRecommendation()}</p>

                {showConnectionStatus && (
                    <div className="flex items-center justify-center gap-2 text-sm">
                        {isOnline ? (
                            <>
                                <Wifi className="h-4 w-4 text-green-500" />
                                <span className="text-green-500">Conectado</span>
                            </>
                        ) : (
                            <>
                                <WifiOff className="h-4 w-4 text-destructive" />
                                <span className="text-destructive">Sin conexión</span>
                            </>
                        )}
                    </div>
                )}

                <div className="flex flex-col gap-2 sm:flex-row sm:justify-center">
                    {canRetry && (
                        <Button onClick={handleRetry} disabled={isRetrying} variant="default" size="sm">
                            {isRetrying ? (
                                <>
                                    <LoadingSpinner size="sm" variant="white" className="mr-2" />
                                    Reintentando...
                                </>
                            ) : (
                                <>
                                    <RefreshCw className="mr-2 h-4 w-4" />
                                    Reintentar
                                    {maxRetries > 1 && ` (${maxRetries - retryCount} restantes)`}
                                </>
                            )}
                        </Button>
                    )}

                    <Button onClick={() => window.location.reload()} variant="outline" size="sm">
                        Recargar página
                    </Button>
                </div>

                {retryCount >= maxRetries && (
                    <div className="mt-4 rounded-lg bg-muted p-3">
                        <p className="text-sm text-muted-foreground">
                            Se agotaron los intentos de reconexión.
                            <br />
                            Recarga la página o contacta al soporte si el problema persiste.
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
};

/**
 * Hook for handling network retry logic
 */
export const useNetworkRetry = (maxRetries: number = 3) => {
    const [retryCount, setRetryCount] = useState(0);
    const [isRetrying, setIsRetrying] = useState(false);

    const retry = useCallback(
        async (retryFunction: () => Promise<void> | void) => {
            if (retryCount >= maxRetries || isRetrying) return;

            setIsRetrying(true);
            try {
                await retryFunction();
                setRetryCount(0); // Reset on success
            } catch (error) {
                setRetryCount((prev) => prev + 1);
                throw error; // Re-throw to let caller handle
            } finally {
                setIsRetrying(false);
            }
        },
        [retryCount, maxRetries, isRetrying],
    );

    const reset = useCallback(() => {
        setRetryCount(0);
        setIsRetrying(false);
    }, []);

    return {
        retryCount,
        isRetrying,
        canRetry: retryCount < maxRetries && !isRetrying,
        retry,
        reset,
    };
};
