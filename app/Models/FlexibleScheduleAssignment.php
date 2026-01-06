<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlexibleScheduleAssignment extends Model
{
    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
    ];

    /**
     * Horarios de entrada permitidos
     */
    public const ALLOWED_START_TIMES = [
        '08:00',
        '08:30',
        '09:00',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope para filtrar por mes y año
     */
    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->where('month', $month)
                     ->where('year', $year);
    }

    /**
     * Obtener el horario formateado
     */
    public function getFormattedStartTimeAttribute(): string
    {
        return substr($this->start_time, 0, 5);
    }

    /**
     * Validar si un horario es permitido
     */
    public static function isValidStartTime(string $time): bool
    {
        return in_array($time, self::ALLOWED_START_TIMES);
    }
}
