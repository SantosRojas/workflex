<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HomeOfficeAssignment extends Model
{
    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'date' => 'date',
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
        return $query->whereMonth('date', $month)
                     ->whereYear('date', $year);
    }

    /**
     * Scope para filtrar por fecha específica
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Contar cuántas personas tienen home office en una fecha
     */
    public static function countForDate($date): int
    {
        return static::forDate($date)->count();
    }

    /**
     * Verificar si se puede agregar más personas en una fecha
     */
    public static function canAddMoreForDate($date): bool
    {
        $maxPeoplePerDay = SystemSetting::getInt('max_people_per_day', 7);
        return static::countForDate($date) < $maxPeoplePerDay;
    }

    /**
     * Obtener cuántos espacios quedan disponibles para una fecha
     */
    public static function availableSlotsForDate($date): int
    {
        $maxPeoplePerDay = SystemSetting::getInt('max_people_per_day', 7);
        return max(0, $maxPeoplePerDay - static::countForDate($date));
    }
}
