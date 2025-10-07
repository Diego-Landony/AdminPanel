import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Calendar, Clock } from 'lucide-react';
import { formatWeekdays, formatPromotionDateRange, formatPromotionTimeRange } from '@/utils/promotion-helpers';

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
export const ValidityBadge = ({
    weekdays,
    validFrom,
    validUntil,
    timeFrom,
    timeUntil
}: ValidityBadgeProps) => {
    const weekdaysText = formatWeekdays(weekdays);
    const dateRange = formatPromotionDateRange(validFrom, validUntil);
    const timeRange = formatPromotionTimeRange(timeFrom, timeUntil);

    const fullText = [weekdaysText, dateRange, timeRange].filter(Boolean).join(' · ');

    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger asChild>
                    <div className="text-sm text-muted-foreground cursor-help">
                        {weekdaysText}
                        {(dateRange || timeRange) && (
                            <div className="flex gap-1 mt-1">
                                {dateRange && (
                                    <Badge variant="outline" className="text-xs">
                                        <Calendar className="h-3 w-3 mr-1" />
                                        Fechas
                                    </Badge>
                                )}
                                {timeRange && (
                                    <Badge variant="outline" className="text-xs">
                                        <Clock className="h-3 w-3 mr-1" />
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
