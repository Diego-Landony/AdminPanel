import { cn } from '@/lib/utils';

interface LoadingSpinnerProps {
    size?: 'xs' | 'sm' | 'md' | 'lg';
    variant?: 'default' | 'white' | 'primary' | 'current';
    className?: string;
}

const sizeMap = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4',
    md: 'h-6 w-6',
    lg: 'h-8 w-8',
} as const;

const variantMap = {
    default: 'border-muted-foreground',
    white: 'border-white dark:border-gray-200',
    primary: 'border-primary',
    current: 'border-current',
} as const;

export function LoadingSpinner({
    size = 'sm',
    variant = 'default',
    className
}: LoadingSpinnerProps) {
    return (
        <div
            className={cn(
                'animate-spin rounded-full border-2 border-t-transparent',
                sizeMap[size],
                variantMap[variant],
                className
            )}
            role="status"
            aria-label="Loading"
        />
    );
}
