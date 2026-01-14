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
        
// Si es manager o admin, obtener usuarios elegibles (incluyéndose a sí mismo)
        $teamMembers = collect();
        if ($user->canManageAssignments()) {
            if ($user->isAdmin()) {
                // Admin puede ver todos los usuarios de áreas permitidas
                $teamMembers = User::whereNotIn('work_area', self::RESTRICTED_AREAS)
                    ->orderBy('work_area')
                    ->orderBy('name')
                    ->get();
            } else {
                // Manager ve usuarios de su área incluyéndose
                $teamMembers = User::where('work_area', $user->work_area)
                    ->whereNotIn('work_area', self::RESTRICTED_AREAS)
                    ->orderBy('name')
                    ->get();
            }
        }
        
        // Horarios permitidos
        $allowedTimes = FlexibleScheduleAssignment::ALLOWED_START_TIMES;
        $allowedLunchTimes = FlexibleScheduleAssignment::ALLOWED_LUNCH_TIMES;
        
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
            'allowedLunchTimes',
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
            'start_time' => ['required', 'regex:/^(0[7-9]|1[0-1]):[0-5][0-9]$/'],
            'lunch_start_time' => ['nullable', 'regex:/^(1[2-4]):[0-5][0-9]$/'],
        ], [
            'start_time.regex' => 'El horario debe estar entre 07:00 y 11:59 AM en formato HH:MM',
            'lunch_start_time.regex' => 'La hora de almuerzo debe estar entre 12:00 y 14:59 en formato HH:MM',
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
            'lunch_start_time' => $request->lunch_start_time ?? '12:00',
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
            'start_time' => ['required', 'regex:/^(0[7-9]|1[0-1]):[0-5][0-9]$/'],
            'lunch_start_time' => ['nullable', 'regex:/^(1[2-4]):[0-5][0-9]$/'],
        ], [
            'start_time.regex' => 'El horario debe estar entre 07:00 y 11:59 AM en formato HH:MM',
            'lunch_start_time.regex' => 'La hora de almuerzo debe estar entre 12:00 y 14:59 en formato HH:MM',
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
            'lunch_start_time' => $request->lunch_start_time ?? $flexibleSchedule->lunch_start_time,
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

    /**
     * Exportar reporte a Excel (CSV)
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->canManageAssignments()) {
            abort(403);
        }
        
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        
        // Obtener asignaciones
        $query = FlexibleScheduleAssignment::with(['user', 'assignedBy'])
            ->forMonth($month, $year);
            
        if (!$user->isAdmin()) {
            $query->whereHas('user', fn($q) => $q->where('work_area', $user->work_area));
        }
        
        $assignments = $query->orderBy('start_time')->get();
        
        // Crear contenido CSV con BOM para Excel
        $monthName = Carbon::create($year, $month, 1)->locale('es')->monthName;
        $filename = "horario_flexible_{$monthName}_{$year}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($assignments) {
            $file = fopen('php://output', 'w');
            
            // BOM para UTF-8 en Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Encabezados
            fputcsv($file, [
                'Empleado',
                'Email',
                'Área',
                'Hora de Entrada',
                'Hora de Almuerzo',
                'Hora de Salida',
                'Asignado por',
                'Fecha de asignación'
            ], ';');
            
            // Datos
            foreach ($assignments as $assignment) {
                $dailyWorkMinutes = \App\Models\SystemSetting::getInt('daily_work_minutes', 480);
                $lunchMinutes = 60;
                $totalMinutes = $dailyWorkMinutes + $lunchMinutes;
                
                $startTime = Carbon::createFromTimeString($assignment->start_time);
                $endTime = $startTime->copy()->addMinutes($totalMinutes);
                
                fputcsv($file, [
                    $assignment->user->name . ' ' . ($assignment->user->last_name ?? ''),
                    $assignment->user->email,
                    $assignment->user->work_area ?? 'Sin área',
                    substr($assignment->start_time, 0, 5),
                    $assignment->lunch_start_time ? substr($assignment->lunch_start_time, 0, 5) : '12:00',
                    $endTime->format('H:i'),
                    $assignment->assignedBy->name ?? 'Sistema',
                    $assignment->created_at->format('d/m/Y H:i')
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
