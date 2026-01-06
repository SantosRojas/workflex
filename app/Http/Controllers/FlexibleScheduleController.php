<?php

namespace App\Http\Controllers;

use App\Models\FlexibleScheduleAssignment;
use App\Models\User;
use App\Services\PlanningPeriodService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FlexibleScheduleController extends Controller
{
    /**
     * Áreas que NO pueden tener horario flexible
     */
    private const RESTRICTED_AREAS = [
        'Servicio al Cliente',
        'Ventas',
        'Facturación',
        'Almacén',
    ];

    /**
     * Mostrar el listado de horarios flexibles
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Obtener mes y año (por defecto el mes actual)
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        
        // Obtener asignaciones del mes
        $query = FlexibleScheduleAssignment::with(['user', 'assignedBy'])
            ->forMonth($month, $year);
            
        // Si no es admin, solo mostrar las de su área
        if (!$user->isAdmin()) {
            $query->whereHas('user', fn($q) => $q->where('work_area', $user->work_area));
        }
        
        $assignments = $query->orderBy('start_time')->get();
        
        // Si es manager o admin, obtener usuarios elegibles de su área
        $teamMembers = collect();
        if ($user->canManageAssignments()) {
            $teamMembers = User::where('work_area', $user->work_area)
                ->whereNotIn('work_area', self::RESTRICTED_AREAS)
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get();
        }
        
        // Horarios permitidos
        $allowedTimes = FlexibleScheduleAssignment::ALLOWED_START_TIMES;
        
        // Verificar período de planificación
        $planningPeriod = PlanningPeriodService::getPlanningPeriodInfo($month, $year);
        
        // Verificar si el área del usuario puede tener horario flexible
        $areaCanHaveFlexible = !in_array($user->work_area, self::RESTRICTED_AREAS);
        
        return view('flexible.index', compact(
            'assignments',
            'teamMembers',
            'month',
            'year',
            'allowedTimes',
            'planningPeriod',
            'areaCanHaveFlexible'
        ));
    }

    /**
     * Almacenar una nueva asignación de horario flexible
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2024',
            'start_time' => 'required|in:08:00,08:30,09:00',
        ]);
        
        $targetUser = User::findOrFail($request->user_id);
        $month = (int) $request->month;
        $year = (int) $request->year;
        
        // Verificar período de planificación (admin puede saltarse esta restricción)
        if (!$user->isAdmin() && !PlanningPeriodService::canPlanForMonth($month, $year)) {
            $periodInfo = PlanningPeriodService::getPlanningPeriodInfo($month, $year);
            return back()->withErrors(['month' => "No estás en el período de planificación. {$periodInfo['message']}"]);
        }
        
        // Validar que el área pueda tener horario flexible
        if (in_array($targetUser->work_area, self::RESTRICTED_AREAS)) {
            return back()->withErrors(['user_id' => 'El área de este usuario no puede tener horario flexible.']);
        }
        
        // Validar que el manager solo pueda asignar a usuarios de su área
        if (!$user->isAdmin() && $targetUser->work_area !== $user->work_area) {
            return back()->withErrors(['user_id' => 'Solo puedes asignar horario flexible a personas de tu área.']);
        }
        
        // Validar permisos
        if (!$user->canManageAssignments()) {
            return back()->withErrors(['error' => 'No tienes permisos para realizar esta acción.']);
        }
        
        // Verificar que no exista ya una asignación para ese mes/año
        $exists = FlexibleScheduleAssignment::where('user_id', $targetUser->id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->exists();
            
        if ($exists) {
            return back()->withErrors(['user_id' => 'Este usuario ya tiene un horario flexible asignado para este mes.']);
        }
        
        // Crear la asignación
        FlexibleScheduleAssignment::create([
            'user_id' => $targetUser->id,
            'assigned_by' => $user->id,
            'month' => $request->month,
            'year' => $request->year,
            'start_time' => $request->start_time,
        ]);
        
        return back()->with('success', 'Horario flexible asignado correctamente.');
    }

    /**
     * Actualizar una asignación existente
     */
    public function update(Request $request, FlexibleScheduleAssignment $flexibleSchedule)
    {
        $user = Auth::user();
        
        $request->validate([
            'start_time' => 'required|in:08:00,08:30,09:00',
        ]);
        
        $month = $flexibleSchedule->month;
        $year = $flexibleSchedule->year;
        
        // Verificar período de planificación (admin puede saltarse esta restricción)
        if (!$user->isAdmin() && !PlanningPeriodService::canPlanForMonth($month, $year)) {
            $periodInfo = PlanningPeriodService::getPlanningPeriodInfo($month, $year);
            return back()->withErrors(['error' => "No puedes modificar asignaciones fuera del período de planificación. {$periodInfo['message']}"]);
        }
        
        // Verificar permisos
        if (!$user->isAdmin() && $flexibleSchedule->user->work_area !== $user->work_area) {
            return back()->withErrors(['error' => 'No tienes permisos para modificar esta asignación.']);
        }
        
        if (!$user->canManageAssignments()) {
            return back()->withErrors(['error' => 'No tienes permisos para realizar esta acción.']);
        }
        
        $flexibleSchedule->update([
            'start_time' => $request->start_time,
            'assigned_by' => $user->id,
        ]);
        
        return back()->with('success', 'Horario flexible actualizado correctamente.');
    }

    /**
     * Eliminar una asignación de horario flexible
     */
    public function destroy(FlexibleScheduleAssignment $flexibleSchedule)
    {
        $user = Auth::user();
        
        $month = $flexibleSchedule->month;
        $year = $flexibleSchedule->year;
        
        // Verificar período de planificación (admin puede saltarse esta restricción)
        if (!$user->isAdmin() && !PlanningPeriodService::canPlanForMonth($month, $year)) {
            $periodInfo = PlanningPeriodService::getPlanningPeriodInfo($month, $year);
            return back()->withErrors(['error' => "No puedes eliminar asignaciones fuera del período de planificación. {$periodInfo['message']}"]);
        }
        
        // Verificar permisos
        if (!$user->isAdmin() && $flexibleSchedule->user->work_area !== $user->work_area) {
            return back()->withErrors(['error' => 'No tienes permisos para eliminar esta asignación.']);
        }
        
        if (!$user->canManageAssignments()) {
            return back()->withErrors(['error' => 'No tienes permisos para realizar esta acción.']);
        }
        
        $flexibleSchedule->delete();
        
        return back()->with('success', 'Asignación de horario flexible eliminada correctamente.');
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
        $query = FlexibleScheduleAssignment::with(['user', 'assignedBy'])
            ->forMonth($month, $year);
            
        if (!$user->isAdmin()) {
            $query->whereHas('user', fn($q) => $q->where('work_area', $user->work_area));
        }
        
        $assignments = $query->orderBy('start_time')->get();
        
        // Agrupar por horario
        $byTime = $assignments->groupBy('start_time');
        
        // Agrupar por área
        $byArea = $assignments->groupBy(fn($a) => $a->user->work_area);
        
        return view('flexible.report', compact('assignments', 'byTime', 'byArea', 'month', 'year'));
    }
}
