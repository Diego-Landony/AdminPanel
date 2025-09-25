import React from 'react';
import { Clock, Calendar, MapPin, Phone, Mail, CreditCard, Star } from 'lucide-react';

/**
 * Utility functions for common UI patterns that appear across multiple components
 * These micro-optimizations extract repeated JSX patterns to improve maintainability
 */

/**
 * Formats a date with a clock icon
 * Used in: customers, restaurants, users, activity tables
 */
export const formatDateWithIcon = (date: string | null, className: string = "text-xs text-muted-foreground") => {
  if (!date) return null;

  return (
    <div className={`flex items-center gap-1 ${className}`}>
      <Clock className="h-3 w-3 text-muted-foreground flex-shrink-0" />
      <span className="truncate">{new Date(date).toLocaleDateString('es-GT', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        timeZone: 'America/Guatemala'
      })}</span>
    </div>
  );
};

/**
 * Formats a date with calendar icon (for creation dates)
 * Used in: all entity tables for created_at fields
 */
export const formatCreatedDateWithIcon = (date: string, className: string = "text-xs text-muted-foreground") => (
  <div className={`flex items-center gap-1 ${className}`}>
    <Calendar className="h-3 w-3 text-muted-foreground flex-shrink-0" />
    <span className="truncate">{new Date(date).toLocaleDateString('es-GT', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })}</span>
  </div>
);

/**
 * Formats location with map pin icon
 * Used in: customers, restaurants tables
 */
export const formatLocationWithIcon = (location: string, className: string = "text-xs text-muted-foreground") => (
  <div className={`flex items-center gap-1 ${className}`}>
    <MapPin className="h-3 w-3 text-muted-foreground flex-shrink-0" />
    <span className="truncate" title={location}>{location}</span>
  </div>
);

/**
 * Formats phone number with phone icon
 * Used in: customers, restaurants tables
 */
export const formatPhoneWithIcon = (phone: string, className: string = "text-xs text-muted-foreground") => (
  <div className={`flex items-center gap-2 ${className}`}>
    <Phone className="h-3 w-3 text-muted-foreground flex-shrink-0" />
    <span className="truncate">{phone}</span>
  </div>
);

/**
 * Formats email with mail icon
 * Used in: users, customers tables
 */
export const formatEmailWithIcon = (email: string, className: string = "text-xs text-muted-foreground") => (
  <div className={`flex items-center gap-2 ${className}`}>
    <Mail className="h-3 w-3 text-muted-foreground flex-shrink-0" />
    <span className="truncate" title={email}>{email}</span>
  </div>
);

/**
 * Formats credit card/subway card with card icon
 * Used in: customers table
 */
export const formatCardWithIcon = (cardNumber: string, className: string = "text-sm") => (
  <div className={`flex items-center gap-2 ${className}`}>
    <CreditCard className="h-4 w-4 text-muted-foreground flex-shrink-0" />
    <code className="text-sm font-mono bg-muted px-2 py-1 rounded truncate">{cardNumber}</code>
  </div>
);

/**
 * Renders star rating with count
 * Used in: restaurants table and cards
 */
export const renderStarsWithCount = (rating: number, totalReviews: number = 0) => {
  const stars = [];
  const fullStars = Math.floor(rating);
  const hasHalfStar = rating % 1 !== 0;

  for (let i = 0; i < 5; i++) {
    if (i < fullStars) {
      stars.push(
        <Star key={i} className="h-3 w-3 fill-yellow-400 text-yellow-400" />
      );
    } else if (i === fullStars && hasHalfStar) {
      stars.push(
        <Star key={i} className="h-3 w-3 fill-yellow-400/50 text-yellow-400" />
      );
    } else {
      stars.push(
        <Star key={i} className="h-3 w-3 text-gray-300" />
      );
    }
  }

  return (
    <div className="flex items-center gap-1">
      <div className="flex">{stars}</div>
      <span className="text-xs text-muted-foreground ml-1">
        ({totalReviews})
      </span>
    </div>
  );
};

/**
 * Common pattern for displaying numeric values with labels
 * Used in: stats displays, point systems, counters
 */
export const formatNumericWithLabel = (
  value: number | string,
  label: string,
  className: string = "text-sm"
) => (
  <div className={className}>
    <div className="font-medium text-foreground tabular-nums">{value}</div>
    <div className="text-xs text-muted-foreground">{label}</div>
  </div>
);

/**
 * Common pattern for status indicators with counts
 * Used in: dashboard stats, table summaries
 */
export const formatStatusWithCount = (
  status: string,
  count: number,
  icon: React.ReactNode,
  className: string = ""
) => (
  <div className={`flex items-center gap-2 ${className}`}>
    {icon}
    <div>
      <div className="font-medium text-foreground tabular-nums">{count}</div>
      <div className="text-xs text-muted-foreground capitalize">{status}</div>
    </div>
  </div>
);

/**
 * Truncated text with tooltip - commonly used pattern
 * Used in: description fields, long names, addresses
 */
export const TruncatedTextWithTooltip = ({
  text,
  maxLength = 50,
  className = "text-sm"
}: {
  text: string;
  maxLength?: number;
  className?: string;
}) => {
  const shouldTruncate = text.length > maxLength;
  const truncatedText = shouldTruncate ? `${text.substring(0, maxLength)}...` : text;

  return (
    <span
      className={`${className} ${shouldTruncate ? 'cursor-help' : ''}`}
      title={shouldTruncate ? text : undefined}
    >
      {truncatedText}
    </span>
  );
};