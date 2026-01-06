<?php

namespace App\Http\Controllers;

use App\Models\HomeOfficeAssignment;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PlanningPeriodService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeOfficeController extends Controller
{
    /**
     * Mostrar el calendario de home office
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Obtener mes y año (por defecto el mes actual)
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        
        // Configuraciones del sistema
        $maxDaysPerMonth = SystemSetting::getInt('max_home_office_days', 2);
        $maxPeoplePerDay = SystemSetting::getInt('max_people_per_day', 7);
        
        // Obtener asignaciones del mes
        $assignments = HomeOfficeAssignment::with(['user', 'assignedBy'])
            ->forMonth($month, $year)
            ->orderBy('date')
            ->get();
        
        // Si es manager o admin, obtener usuarios de su área
        $teamMembers = collect();
        if ($user->canManageAssignments()) {
            $teamMembers = User::where('work_area', $user->work_area)
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get();
        }
        
        // Generar días del mes con información
        $daysInMonth = $this->generateMonthDays($month, $year, $assignments, $maxPeoplePerDay);
        
        // Verificar período de planificación
        $planningPeriod = PlanningPeriodService::getPlanningPeriodInfo($month, $year);
        
        return view('homeoffice.index', compact(
            'assignments',
            'teamMembers',
            'month',
            'year',
            'maxDaysPerMonth',
            'maxPeoplePerDay',
            'daysInMonth',
            'planningPeriod'
        ));
    }

    /**
     * Almacenar una nueva asignación de home office
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
        ]);
        
        $targetUser = User::findOrFail($request->user_id);
        $date = Carbon::parse($request->date);
        $month = $date->month;
        $year = $date->year;
        
        // Verificar período de planificación (admin puede saltarse esta restricción)
        if (!$user->isAdmin() && !PlanningPeriodService::canPlanForMonth($month, $year)) {
            $periodInfo = PlanningPeriodService::getPlanningPeriodInfo($month, $year);
            return back()->withErrors(['date' => "No estás en el período de planificación. {$periodInfo['message']}"]);
        }
        
        // Validar que el manager solo pueda asignar a usuarios de su área
        if (!$user->isAdmin() && $targetUser->work_area !== $user->work_area) {
            return back()->withErrors(['user_id' => 'Solo puedes asignar home office a personas de tu área.']);
        }
        
        // Validar que el usuario tenga permiso para asignar
        if (!$user->canManageAssignments()) {
            return back()->withErrors(['error' => 'No tienes permisos para realizar esta acción.']);
        }
        
        // Verificar límite de días por mes para el usuario
        $maxDaysPerMonth = SystemSetting::getInt('max_home_office_days', 2);
        $currentDays = $targetUser->homeOfficeDaysInMonth($month, $year);
        
        if ($currentDays >= $maxDaysPerMonth) {
            return back()->withErrors(['date' => "El usuario ya tiene {$maxDaysPerMonth} días de home office asignados este mes."]);
        }
        
        // Verificar límite de personas por día
        if (!HomeOfficeAssignment::canAddMoreForDate($date)) {
            $maxPeoplePerDay = SystemSetting::getInt('max_people_per_day', 7);
            return back()->withErrors(['date' => "Ya hay {$maxPeoplePerDay} personas en home office para esta fecha."]);
        }
        
        // Verificar que no exista ya una asignación para ese día
        $exists = HomeOfficeAssignment::where('user_id', $targetUser->id)
            ->whereDate('date', $date)
            ->exists();
            
        if ($exists) {
            return back()->withErrors(['date' => 'Este usuario ya tiene home office asignado para esta fecha.']);
        }
        
        // Crear la asignación
        HomeOfficeAssignment::create([
            'user_id' => $targetUser->id,
            'assigned_by' => $user->id,
            'date' => $date,
        ]);
        
        return back()->with('success', 'Día de home office asignado correctamente.');
    }

    /**
     * Eliminar una asignación de home office
     */
    public function destroy(HomeOfficeAssignment $homeOffice)
    {
        $user = Auth::user();
        
        $date = $homeOffice->date;
        $month = $date->month;
        $year = $date->year;
        
        // Verificar período de planificación para eliminar (admin puede saltarse esta restricción)
        if (!$user->isAdmin() && !PlanningPeriodService::canPlanForMonth($month, $year)) {
            $periodInfo = PlanningPeriodService::getPlanningPeriodInfo($month, $year);
            return back()->withErrors(['error' => "No puedes eliminar asignaciones fuera del período de planificación. {$periodInfo['message']}"]);
        }
        
        // Verificar permisos
        if (!$user->isAdmin() && $homeOffice->user->work_area !== $user->work_area) {
            return back()->withErrors(['error' => 'No tienes permisos para eliminar esta asignación.']);
        }
        
        if (!$user->canManageAssignments()) {
            return back()->withErrors(['error' => 'No tienes permisos para realizar esta acción.']);
        }
        
        $homeOffice->delete();
        
        return back()->with('success', 'Asignación de home office eliminada correctamente.');
    }

    /**
     * Generar información de los días del mes
     */
    private function generateMonthDays(int $month, int $year, $assignments, int $maxPeoplePerDay): array
    {
        $startOfMonth = Carbon::create($year, $month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        
        $days = [];
        $current = $startOfMonth->copy();
        
        while ($current <= $endOfMonth) {
            $dateStr = $current->toDateString();
            $dayAssignments = $assignments->filter(fn($a) => $a->date->toDateString() === $dateStr);
            
            $days[] = [
                'date' => $current->copy(),
                'dayOfWeek' => $current->dayOfWeek,
                'dayName' => $current->locale('es')->dayName,
                'isWeekend' => $current->isWeekend(),
                'isToday' => $current->isToday(),
                'isPast' => $current->isPast() && !$current->isToday(),
                'assignments' => $dayAssignments,
                'count' => $dayAssignments->count(),
                'available' => $maxPeoplePerDay - $dayAssignments->count(),
                'isFull' => $dayAssignments->count() >= $maxPeoplePerDay,
            ];
            
            $current->addDay();
        }
        
        return $days;
    }

    /**
     * Vista de resumen/reporte
     */
    public function report(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->canManageAssignments()) {
            abort(403);
        }
        
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        
        // Obtener estadísticas
        $query = HomeOfficeAssignment::with(['user', 'assignedBy'])
            ->forMonth($month, $year);
            
        if (!$user->isAdmin()) {
            $query->whereHas('user', fn($q) => $q->where('work_area', $user->work_area));
        }
        
        $assignments = $query->orderBy('date')->get();
        
        // Agrupar por usuario
        $byUser = $assignments->groupBy('user_id')->map(function ($items) {
            return [
                'user' => $items->first()->user,
                'count' => $items->count(),
                'dates' => $items->pluck('date'),
            ];
        });
        
        // Agrupar por fecha
        $byDate = $assignments->groupBy(fn($a) => $a->date->toDateString());
        
        return view('homeoffice.report', compact('assignments', 'byUser', 'byDate', 'month', 'year'));
    }
}
