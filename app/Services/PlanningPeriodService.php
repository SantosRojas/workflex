<?php

namespace App\Services;

use Carbon\Carbon;

class PlanningPeriodService
{
    /**
     * Obtener información del período de planificación para un mes específico
     * 
     * - Para Enero: planificación del 05 al 09 de enero
     * - Para otros meses: última semana del mes previo
     */
    public static function getPlanningPeriod(int $month, int $year): array
    {
        // Excepción para enero: planificación del 05 al 09
        if ($month === 1) {
            $planningStart = Carbon::create($year, 1, 5)->startOfDay();
            $planningEnd = Carbon::create($year, 1, 9)->endOfDay();
        } else {
            // Última semana del mes previo
            $prevMonth = Carbon::create($year, $month, 1)->subMonth();
            $lastDayPrevMonth = $prevMonth->copy()->endOfMonth();
            
            // Comenzar desde el lunes de la última semana del mes previo
            $planningStart = $lastDayPrevMonth->copy()->startOfWeek()->startOfDay();
            $planningEnd = $lastDayPrevMonth->copy()->endOfDay();
        }
        
        return [
            'start' => $planningStart,
            'end' => $planningEnd,
        ];
    }

    /**
     * Verificar si actualmente estamos en período de planificación para un mes específico
     */
    public static function isInPlanningPeriod(int $month, int $year): bool
    {
        $now = Carbon::now();
        $period = self::getPlanningPeriod($month, $year);
        
        return $now->between($period['start'], $period['end']);
    }

    /**
     * Verificar si se puede planificar para un mes/año específico
     * Considera el período de planificación activo
     */
    public static function canPlanForMonth(int $month, int $year): bool
    {
        return self::isInPlanningPeriod($month, $year);
    }

    /**
     * Verificar si se puede planificar para una fecha específica (home office)
     * La fecha debe estar en un mes cuyo período de planificación esté activo
     */
    public static function canPlanForDate(Carbon $date): bool
    {
        return self::isInPlanningPeriod($date->month, $date->year);
    }

    /**
     * Obtener el mensaje descriptivo del período de planificación
     */
    public static function getPlanningPeriodMessage(int $month, int $year): string
    {
        $period = self::getPlanningPeriod($month, $year);
        $isActive = self::isInPlanningPeriod($month, $year);
        
        $monthName = Carbon::create($year, $month, 1)->locale('es')->monthName;
        
        if ($isActive) {
            return "✅ Período de planificación ACTIVO para {$monthName}: del {$period['start']->format('d/m')} al {$period['end']->format('d/m')}";
        }
        
        return "⏰ El período de planificación para {$monthName} es del {$period['start']->format('d/m')} al {$period['end']->format('d/m')}";
    }

    /**
     * Obtener información completa del período para las vistas
     */
    public static function getPlanningPeriodInfo(int $month, int $year): array
    {
        $period = self::getPlanningPeriod($month, $year);
        $isActive = self::isInPlanningPeriod($month, $year);
        
        return [
            'start' => $period['start'],
            'end' => $period['end'],
            'isActive' => $isActive,
            'message' => self::getPlanningPeriodMessage($month, $year),
            'canPlan' => $isActive,
        ];
    }
}
