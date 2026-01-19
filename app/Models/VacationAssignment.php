<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VacationAssignment extends Model
{
    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'weekend_days' => 'integer',
        'effective_days' => 'integer',
    ];

    /**
     * Total de días de vacaciones que corresponden por año (equivalente a un mes calendario)
     */
    public const TOTAL_VACATION_DAYS = 30;

    /**
     * Máximo de fines de semana que cuentan como vacaciones (4 sábados + 4 domingos)
     */
    public const MAX_WEEKEND_DAYS = 8;

    /**
     * Relación con el usuario que tiene las vacaciones
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el usuario que asignó las vacaciones
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope para filtrar por año fiscal
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Calcular los días calendario de un período (incluyendo fines de semana)
     */
    public static function calculateCalendarDays(Carbon $startDate, Carbon $endDate): int
    {
        return $startDate->diffInDays($endDate) + 1; // +1 para incluir ambos días
    }

    /**
     * Contar días de fin de semana (sábados y domingos) en un período
     */
    public static function countWeekendDays(Carbon $startDate, Carbon $endDate): int
    {
        $weekendDays = 0;
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            if ($current->isWeekend()) {
                $weekendDays++;
            }
            $current->addDay();
        }
        
        return $weekendDays;
    }

    /**
     * Contar días hábiles (lunes a viernes) en un período
     */
    public static function countWeekdays(Carbon $startDate, Carbon $endDate): int
    {
        $weekdays = 0;
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            if ($current->isWeekday()) {
                $weekdays++;
            }
            $current->addDay();
        }
        
        return $weekdays;
    }

    /**
     * Obtener el total de días de fin de semana ya consumidos por un usuario en un año
     */
    public static function getTotalWeekendDaysUsed(int $userId, int $year): int
    {
        $assignments = static::forUser($userId)->forYear($year)->get();
        $totalWeekendDays = 0;
        
        foreach ($assignments as $assignment) {
            $totalWeekendDays += static::countWeekendDays($assignment->start_date, $assignment->end_date);
        }
        
        // Máximo 8 días de fin de semana cuentan
        return min($totalWeekendDays, self::MAX_WEEKEND_DAYS);
    }

    /**
     * Calcular los días efectivos que se descontarán de las vacaciones para un nuevo período
     * considerando el límite de fines de semana (máx 8 días)
     */
    public static function calculateEffectiveDays(int $userId, int $year, Carbon $startDate, Carbon $endDate, ?int $excludeId = null): array
    {
        // Obtener fines de semana ya consumidos (excluyendo la asignación actual si se está editando)
        $assignments = static::forUser($userId)->forYear($year);
        if ($excludeId) {
            $assignments = $assignments->where('id', '!=', $excludeId);
        }
        $assignments = $assignments->get();
        
        $weekendDaysUsed = 0;
        foreach ($assignments as $assignment) {
            $weekendDaysUsed += static::countWeekendDays($assignment->start_date, $assignment->end_date);
        }
        $weekendDaysUsed = min($weekendDaysUsed, self::MAX_WEEKEND_DAYS);
        
        // Calcular días del nuevo período
        $calendarDays = static::calculateCalendarDays($startDate, $endDate);
        $weekendDaysInPeriod = static::countWeekendDays($startDate, $endDate);
        $weekdaysInPeriod = static::countWeekdays($startDate, $endDate);
        
        // Cuántos días de fin de semana aún pueden contar
        $remainingWeekendQuota = max(0, self::MAX_WEEKEND_DAYS - $weekendDaysUsed);
        
        // Días de fin de semana que contarán en este período
        $weekendDaysToCount = min($weekendDaysInPeriod, $remainingWeekendQuota);
        
        // Días efectivos = días hábiles + fines de semana que aún cuentan
        $effectiveDays = $weekdaysInPeriod + $weekendDaysToCount;
        
        return [
            'calendar_days' => $calendarDays,
            'weekend_days_in_period' => $weekendDaysInPeriod,
            'weekdays_in_period' => $weekdaysInPeriod,
            'weekend_days_used' => $weekendDaysUsed,
            'remaining_weekend_quota' => $remainingWeekendQuota,
            'weekend_days_to_count' => $weekendDaysToCount,
            'effective_days' => $effectiveDays,
        ];
    }

    /**
     * Obtener el total de días de vacaciones usados por un usuario en un año
     * Considera el límite de 8 días de fin de semana
     */
    public static function getTotalDaysUsed(int $userId, int $year): int
    {
        $assignments = static::forUser($userId)->forYear($year)->get();
        
        if ($assignments->isEmpty()) {
            return 0;
        }
        
        $totalWeekdays = 0;
        $totalWeekendDays = 0;
        
        foreach ($assignments as $assignment) {
            $totalWeekdays += static::countWeekdays($assignment->start_date, $assignment->end_date);
            $totalWeekendDays += static::countWeekendDays($assignment->start_date, $assignment->end_date);
        }
        
        // Solo contar máximo 8 días de fin de semana
        $effectiveWeekendDays = min($totalWeekendDays, self::MAX_WEEKEND_DAYS);
        
        return $totalWeekdays + $effectiveWeekendDays;
    }

    /**
     * Obtener los días de vacaciones disponibles para un usuario en un año
     */
    public static function getAvailableDays(int $userId, int $year): int
    {
        $used = static::getTotalDaysUsed($userId, $year);
        return max(0, self::TOTAL_VACATION_DAYS - $used);
    }

    /**
     * Verificar si se pueden asignar más días de vacaciones
     */
    public static function canAssignMoreDays(int $userId, int $year, int $effectiveDaysToAssign): bool
    {
        $available = static::getAvailableDays($userId, $year);
        return $effectiveDaysToAssign <= $available;
    }

    /**
     * Verificar si hay solapamiento con vacaciones existentes
     */
    public static function hasOverlap(int $userId, Carbon $startDate, Carbon $endDate, ?int $excludeId = null): bool
    {
        $query = static::forUser($userId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($inner) use ($startDate, $endDate) {
                    $inner->where('start_date', '<=', $endDate)
                          ->where('end_date', '>=', $startDate);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Obtener información resumida de vacaciones para un usuario
     */
    public static function getVacationSummary(int $userId, int $year): array
    {
        $totalDays = self::TOTAL_VACATION_DAYS;
        $usedDays = static::getTotalDaysUsed($userId, $year);
        $availableDays = max(0, $totalDays - $usedDays);
        $assignments = static::forUser($userId)->forYear($year)->orderBy('start_date')->get();
        $weekendDaysUsed = static::getTotalWeekendDaysUsed($userId, $year);
        $remainingWeekendQuota = max(0, self::MAX_WEEKEND_DAYS - $weekendDaysUsed);

        return [
            'total_days' => $totalDays,
            'used_days' => $usedDays,
            'available_days' => $availableDays,
            'assignments' => $assignments,
            'percentage_used' => round(($usedDays / $totalDays) * 100, 1),
            'weekend_days_used' => $weekendDaysUsed,
            'remaining_weekend_quota' => $remainingWeekendQuota,
            'max_weekend_days' => self::MAX_WEEKEND_DAYS,
        ];
    }

    /**
     * Obtener el formato de fecha legible del período
     */
    public function getFormattedPeriodAttribute(): string
    {
        $start = $this->start_date->locale('es')->isoFormat('D [de] MMMM');
        $end = $this->end_date->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
        return "{$start} al {$end}";
    }

    /**
     * Obtener los días de fin de semana de esta asignación
     * Usa el valor de BD si existe, si no lo calcula
     */
    public function getWeekendDaysCountAttribute(): int
    {
        if ($this->attributes['weekend_days'] ?? null) {
            return $this->attributes['weekend_days'];
        }
        return static::countWeekendDays($this->start_date, $this->end_date);
    }

    /**
     * Obtener los días hábiles de esta asignación
     */
    public function getWeekdaysAttribute(): int
    {
        return static::countWeekdays($this->start_date, $this->end_date);
    }

    /**
     * Obtener los días efectivos de esta asignación
     * Usa el valor de BD si existe, si no lo calcula
     */
    public function getEffectiveDaysCountAttribute(): int
    {
        if ($this->attributes['effective_days'] ?? null) {
            return $this->attributes['effective_days'];
        }
        // Calcular usando el método existente
        $summary = static::getVacationSummary($this->user_id, $this->year);
        return $summary['used_days'];
    }

    /**
     * Obtener estado del período de vacaciones
     */
    public function getStatusAttribute(): string
    {
        $today = Carbon::today();
        
        if ($this->end_date->lt($today)) {
            return 'completed'; // Ya terminó
        } elseif ($this->start_date->lte($today) && $this->end_date->gte($today)) {
            return 'active'; // En curso
        } else {
            return 'upcoming'; // Próximo
        }
    }

    /**
     * Obtener etiqueta de estado formateada
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'completed' => 'Completado',
            'active' => 'En curso',
            'upcoming' => 'Próximo',
            default => 'Desconocido',
        };
    }

    /**
     * Obtener clase CSS para el estado
     */
    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            'completed' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'upcoming' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
