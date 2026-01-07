<?php

namespace App\Http\Controllers;

use App\Models\HomeOfficeAssignment;
use App\Models\FlexibleScheduleAssignment;
use App\Models\SystemSetting;
use App\Services\PlanningPeriodService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();
        
        // Obtener mes y año desde query string, con validación
        $currentMonth = (int) $request->get('month', now()->month);
        $currentYear = (int) $request->get('year', now()->year);
        
        // Validar que sean valores válidos
        $currentMonth = max(1, min(12, $currentMonth));
        $currentYear = max(2020, min(2099, $currentYear));

        // Configuraciones del sistema
        $maxHomeOfficeDays = SystemSetting::getInt('max_home_office_days', 2);

        // Estadísticas de Home Office del usuario
        $myHomeOfficeDays = $user->homeOfficeDaysInMonth($currentMonth, $currentYear);

        // Próximo día de home office del usuario
        $nextHomeOffice = HomeOfficeAssignment::where('user_id', $user->id)
            ->where('date', '>=', now())
            ->orderBy('date')
            ->first();

        // Horario flexible del usuario este mes
        $myFlexibleSchedule = FlexibleScheduleAssignment::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->first();

        // Período de planificación
        $planningPeriod = PlanningPeriodService::getPlanningPeriodInfo($currentMonth, $currentYear);

        // Asignaciones de home office del mes (visibles para todos)
        $homeOfficeAssignments = HomeOfficeAssignment::with('user')
            ->forMonth($currentMonth, $currentYear)
            ->orderBy('date')
            ->get();

        // Personas en home office hoy (visibles para todos)
        $teamHomeOfficeToday = HomeOfficeAssignment::with('user')
            ->whereDate('date', now())
            ->get();

        // Datos adicionales para managers/admin
        $teamFlexibleCount = 0;
        $flexibleAssignments = collect();

        if ($user->canManageAssignments()) {
            // Asignaciones de horario flexible del mes
            $flexibleAssignments = FlexibleScheduleAssignment::with('user')
                ->forMonth($currentMonth, $currentYear)
                ->when(!$user->isAdmin(), function ($query) use ($user) {
                    $query->whereHas('user', fn($q) => $q->where('work_area', $user->work_area));
                })
                ->orderBy('start_time')
                ->get();

            $teamFlexibleCount = $flexibleAssignments->count();
        }

        // Horarios permitidos para mostrar en el resumen
        $allowedTimes = FlexibleScheduleAssignment::ALLOWED_START_TIMES;

        // Datos para JavaScript - Home Office agrupado por fecha
        $homeOfficeByDate = $homeOfficeAssignments->groupBy(function($a) {
            return $a->date->format('Y-m-d');
        })->map(function($items) {
            return $items->map(function($a) {
                return ['name' => $a->user->name, 'area' => $a->user->work_area];
            })->values();
        });

        // Datos para JavaScript - Horarios Flexibles agrupados por área
        $flexibleByArea = [];
        if ($user->canManageAssignments()) {
            $flexibleByArea = $flexibleAssignments->groupBy(function($a) {
                return $a->user->work_area;
            })->map(function($items) {
                return $items->map(function($a) {
                    return ['name' => $a->user->name, 'time' => substr($a->start_time, 0, 5)];
                })->sortBy('time')->values();
            });
        }

        return view('dashboard', compact(
            'user',
            'currentMonth',
            'currentYear',
            'maxHomeOfficeDays',
            'myHomeOfficeDays',
            'nextHomeOffice',
            'myFlexibleSchedule',
            'planningPeriod',
            'teamHomeOfficeToday',
            'teamFlexibleCount',
            'homeOfficeAssignments',
            'flexibleAssignments',
            'allowedTimes',
            'homeOfficeByDate',
            'flexibleByArea'
        ));
    }
}
