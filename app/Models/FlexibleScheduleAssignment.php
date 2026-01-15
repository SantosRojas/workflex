<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlexibleScheduleAssignment extends Model
{
    protected $guarded = [
        'id'
    ];

    /**
     * Horarios de entrada predefinidos (sugerencias)
     */
    public const ALLOWED_START_TIMES = [
        '08:00',
        '08:30',
        '09:00',
    ];

    /**
     * Horarios de almuerzo predefinidos
     */
    public const ALLOWED_LUNCH_TIMES = [
        '11:00',
        '12:00',
        '13:00',
        '14:00',
        '15:00'
    ];

    /**
     * Obtener la hora de inicio formateada
     */
    public function getStartTimeFormattedAttribute(): string
    {
        return substr($this->start_time, 0, 5);
    }

    /**
     * Obtener la hora de almuerzo formateada
     */
    public function getLunchStartTimeFormattedAttribute(): string
    {
        return $this->lunch_start_time ? substr($this->lunch_start_time, 0, 5) : '12:00';
    }

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
}
