import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { formatPromotionDateRange, formatPromotionTimeRange, formatWeekdays } from '@/utils/promotion-helpers';
import { Calendar, Clock } from 'lucide-react';

interface ValidityBadgeProps {
    weekdays: number[] | null;
    validFrom: string | null;
    validUntil: string | null;
    timeFrom: string | null;
    timeUntil: string | null;
}

/**
 * Componente reutilizable para mostrar la vigencia de una promoción
 * Muestra días de la semana, rango de fechas y rango de horarios
 * con tooltips informativos
 */
export const ValidityBadge = ({ weekdays, validFrom, validUntil, timeFrom, timeUntil }: ValidityBadgeProps) => {
    const weekdaysText = formatWeekdays(weekdays);
    const dateRange = formatPromotionDateRange(validFrom, validUntil);
    const timeRange = formatPromotionTimeRange(timeFrom, timeUntil);

    const fullText = [weekdaysText, dateRange, timeRange].filter(Boolean).join(' · ');

    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger asChild>
                    <div className="cursor-help text-sm text-muted-foreground">
                        {weekdaysText}
                        {(dateRange || timeRange) && (
                            <div className="mt-1 flex gap-1">
                                {dateRange && (
                                    <Badge variant="outline" className="text-xs">
                                        <Calendar className="mr-1 h-3 w-3" />
                                        Fechas
                                    </Badge>
                                )}
                                {timeRange && (
                                    <Badge variant="outline" className="text-xs">
                                        <Clock className="mr-1 h-3 w-3" />
                                        Horario
                                    </Badge>
                                )}
                            </div>
                        )}
                    </div>
                </TooltipTrigger>
                <TooltipContent className="max-w-xs">
                    <div className="space-y-1">
                        <p className="font-medium">{fullText}</p>
                        {dateRange && <p className="text-xs">📅 {dateRange}</p>}
                        {timeRange && <p className="text-xs">🕐 {timeRange}</p>}
                    </div>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
};
