import React from 'react';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Card, CardContent, CardHeader } from '@/components/ui/card';

interface ResponsiveCardProps {
  children: React.ReactNode;
  className?: string;
}

/**
 * Professional responsive card container with touch optimizations
 */
export const ResponsiveCard: React.FC<ResponsiveCardProps> = ({
  children,
  className = ""
}) => {
  return (
    <Card className={`
      hover:shadow-md hover:border-border/80
      transition-all duration-200
      active:scale-[0.98] active:shadow-sm
      ${className}
    `}>
      {children}
    </Card>
  );
};

interface ResponsiveCardHeaderProps {
  avatar?: {
    src?: string;
    fallback: string;
    className?: string;
  };
  icon?: React.ReactNode;
  title: React.ReactNode;
  subtitle?: React.ReactNode;
  badge?: {
    children: React.ReactNode;
    variant?: 'default' | 'secondary' | 'destructive' | 'outline';
    className?: string;
  };
  className?: string;
}

/**
 * Professional card header with avatar/icon and metadata
 */
export const ResponsiveCardHeader: React.FC<ResponsiveCardHeaderProps> = ({
  avatar,
  icon,
  title,
  subtitle,
  badge,
  className = ""
}) => {
  return (
    <CardHeader className={`pb-4 ${className}`}>
      <div className="flex items-start justify-between gap-4">
        <div className="flex items-start gap-4 flex-1 min-w-0">
          {/* Avatar or Icon */}
          {avatar ? (
            <Avatar className={`w-10 h-10 flex-shrink-0 ${avatar.className || ''}`}>
              {avatar.src && <AvatarImage src={avatar.src} />}
              <AvatarFallback className="text-sm font-medium">
                {avatar.fallback}
              </AvatarFallback>
            </Avatar>
          ) : icon ? (
            <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
              {icon}
            </div>
          ) : null}

          {/* Title and Subtitle */}
          <div className="min-w-0 flex-1 space-y-1">
            <h3 className="font-semibold leading-tight truncate">
              {title}
            </h3>
            {subtitle && (
              <p className="text-sm text-muted-foreground truncate">
                {subtitle}
              </p>
            )}
          </div>
        </div>

        {/* Badge */}
        {badge && (
          <Badge
            variant={badge.variant || 'default'}
            className={`flex-shrink-0 ${badge.className || ''}`}
          >
            {badge.children}
          </Badge>
        )}
      </div>
    </CardHeader>
  );
};

interface ResponsiveCardContentProps {
  children: React.ReactNode;
  columns?: 1 | 2;
  className?: string;
}

/**
 * Professional card content with responsive grid layout
 */
export const ResponsiveCardContent: React.FC<ResponsiveCardContentProps> = ({
  children,
  columns = 2,
  className = ""
}) => {
  const gridClass = columns === 2 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1';

  return (
    <CardContent className={`pt-0 ${className}`}>
      <div className={`grid ${gridClass} gap-4 text-sm`}>
        {children}
      </div>
    </CardContent>
  );
};

interface DataFieldProps {
  label: string;
  value: React.ReactNode;
  className?: string;
}

/**
 * Professional data field component for label/value display
 */
export const DataField: React.FC<DataFieldProps> = ({
  label,
  value,
  className = ""
}) => {
  return (
    <div className={`space-y-1 ${className}`}>
      <dt className="font-medium text-muted-foreground text-sm">
        {label}
      </dt>
      <dd className="text-foreground">
        {value}
      </dd>
    </div>
  );
};

interface CardActionsProps {
  children: React.ReactNode;
  className?: string;
}

/**
 * Professional card actions area
 */
export const CardActions: React.FC<CardActionsProps> = ({
  children,
  className = ""
}) => {
  return (
    <div className={`flex items-center justify-end pt-4 border-t border-border ${className}`}>
      {children}
    </div>
  );
};

interface BadgeGroupProps {
  children: React.ReactNode;
  className?: string;
}

/**
 * Professional badge group for multiple badges
 */
export const BadgeGroup: React.FC<BadgeGroupProps> = ({
  children,
  className = ""
}) => {
  return (
    <div className={`flex flex-wrap gap-1.5 ${className}`}>
      {children}
    </div>
  );
};

/**
 * Utility function for generating professional avatar initials
 */
export const generateAvatarInitials = (name: string): string => {
  return name
    .trim()
    .split(/\s+/)
    .map(word => word.charAt(0).toUpperCase())
    .slice(0, 2)
    .join('');
};

/**
 * Professional responsive card wrapper that includes all card components
 */
interface ResponsiveCardWrapperProps {
  avatar?: ResponsiveCardHeaderProps['avatar'];
  icon?: React.ReactNode;
  title: React.ReactNode;
  subtitle?: React.ReactNode;
  badge?: ResponsiveCardHeaderProps['badge'];
  children: React.ReactNode;
  actions?: React.ReactNode;
  className?: string;
}

export const ResponsiveCardWrapper: React.FC<ResponsiveCardWrapperProps> = ({
  avatar,
  icon,
  title,
  subtitle,
  badge,
  children,
  actions,
  className
}) => {
  return (
    <ResponsiveCard className={className}>
      <ResponsiveCardHeader
        avatar={avatar}
        icon={icon}
        title={title}
        subtitle={subtitle}
        badge={badge}
      />

      <ResponsiveCardContent>
        {children}
      </ResponsiveCardContent>

      {actions && (
        <CardActions>
          {actions}
        </CardActions>
      )}
    </ResponsiveCard>
  );
};