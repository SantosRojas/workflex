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
        
        // Obtener mes y año actuales
        $now = now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // Calcular el próximo mes
        $nextMonthObj = $now->copy()->addMonth();
        $nextMonth = $nextMonthObj->month;
        $nextYear = $nextMonthObj->year;

        // Si no se especificó un mes/año en el request
        if (!$request->has('month') && !$request->has('year')) {
            // Verificar si el periodo de planificación para el PRÓXIMO mes ya está activo
            if (PlanningPeriodService::isInPlanningPeriod($nextMonth, $nextYear)) {
                // Redirigir por defecto al próximo mes si su periodo está activo
                $month = $nextMonth;
                $year = $nextYear;
            } else {
                $month = $currentMonth;
                $year = $currentYear;
            }
        } else {
            // Obtener mes y año del request
            $month = (int) $request->get('month', $currentMonth);
            $year = (int) $request->get('year', $currentYear);
        }
        
        // Meses disponibles para navegación (Mes actual y Siguiente)
        $availableMonths = [
            [
                'month' => $currentMonth,
                'year' => $currentYear,
                'name' => Carbon::create($currentYear, $currentMonth, 1)->locale('es')->monthName,
                'isCurrent' => true
            ],
            [
                'month' => $nextMonth,
                'year' => $nextYear,
                'name' => Carbon::create($nextYear, $nextMonth, 1)->locale('es')->monthName,
                'isCurrent' => false
            ]
        ];

        // Configuraciones del sistema
        $maxDaysPerMonth = SystemSetting::getInt('max_home_office_days', 2);
        $maxPeoplePerDay = SystemSetting::getInt('max_people_per_day', 7);
        
        // Obtener asignaciones del mes
        $assignmentsQuery = HomeOfficeAssignment::with(['user', 'assignedBy'])
            ->forMonth($month, $year);
        
        // Si es manager (no admin), filtrar solo por su área
        if ($user->canManageAssignments() && !$user->isAdmin()) {
            $assignmentsQuery->whereHas('user', fn($q) => $q->where('work_area', $user->work_area));
        }
        
        $assignments = $assignmentsQuery->orderBy('date')->get();
        
        // Si es manager o admin, obtener usuarios (incluyéndose a sí mismo)
        $teamMembers = collect();
        if ($user->canManageAssignments()) {
            if ($user->isAdmin()) {
                // Admin puede ver todos los usuarios incluyéndose
                $teamMembers = User::orderBy('work_area')
                    ->orderBy('name')
                    ->get();
            } else {
                // Manager ve usuarios de su área incluyéndose
                $teamMembers = User::where('work_area', $user->work_area)
                    ->orderBy('name')
                    ->get();
            }
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
            'planningPeriod',
            'availableMonths'
        ));
    }

    /**
     * Almacenar una nueva asignación de home office (soporta múltiples fechas)
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'dates' => 'required|string',
        ]);
        
        $targetUser = User::findOrFail($request->user_id);
        
        // Parsear las fechas (vienen separadas por coma del flatpickr)
        $datesInput = array_map('trim', explode(',', $request->dates));
        $datesInput = array_filter($datesInput); // Eliminar vacíos
        
        if (empty($datesInput)) {
            return back()->withErrors(['dates' => 'Debes seleccionar al menos una fecha.']);
        }
        
        // Validar que el usuario tenga permiso para asignar
        if (!$user->canManageAssignments()) {
            return back()->withErrors(['error' => 'No tienes permisos para realizar esta acción.']);
        }
        
        // Validar que el manager solo pueda asignar a usuarios de su área
        if (!$user->isAdmin() && $targetUser->work_area !== $user->work_area) {
            return back()->withErrors(['user_id' => 'Solo puedes asignar home office a personas de tu área.']);
        }
        
        $maxDaysPerMonth = SystemSetting::getInt('max_home_office_days', 2);
        $maxPeoplePerDay = SystemSetting::getInt('max_people_per_day', 7);
        
        $errors = [];
        $assignedDates = [];
        $skippedDates = [];
        
        // Agrupar fechas por mes para validar límites mensuales
        $datesByMonth = [];
        foreach ($datesInput as $dateStr) {
            try {
                $date = Carbon::parse($dateStr);
                if ($date->lt(Carbon::today())) {
                    $skippedDates[] = $date->format('d/m/Y') . ' (fecha pasada)';
                    continue;
                }
                $monthKey = $date->format('Y-m');
                $datesByMonth[$monthKey][] = $date;
            } catch (\Exception $e) {
                $errors[] = "Fecha inválida: {$dateStr}";
            }
        }
        
        foreach ($datesByMonth as $monthKey => $dates) {
            $firstDate = $dates[0];
            $month = $firstDate->month;
            $year = $firstDate->year;
            
            // Verificar período de planificación (admin puede saltarse esta restricción)
            if (!$user->isAdmin() && !PlanningPeriodService::canPlanForMonth($month, $year)) {
                $periodInfo = PlanningPeriodService::getPlanningPeriodInfo($month, $year);
                $errors[] = "No estás en el período de planificación para " . $firstDate->locale('es')->monthName . ". {$periodInfo['message']}";
                continue;
            }
            
            // Verificar límite de días por mes para el usuario
            $currentDays = $targetUser->homeOfficeDaysInMonth($month, $year);
            $availableDays = $maxDaysPerMonth - $currentDays;
            
            if ($availableDays <= 0) {
                $errors[] = "El usuario ya tiene {$maxDaysPerMonth} días de home office asignados en " . $firstDate->locale('es')->monthName . ".";
                continue;
            }
            
            $assignedInThisMonth = 0;
            foreach ($dates as $date) {
                // Verificar si ya alcanzó el límite con las asignaciones de esta solicitud
                if ($assignedInThisMonth >= $availableDays) {
                    $skippedDates[] = $date->format('d/m/Y') . ' (límite mensual alcanzado)';
                    continue;
                }
                
                // Verificar límite de personas por día
                if (!HomeOfficeAssignment::canAddMoreForDate($date)) {
                    $skippedDates[] = $date->format('d/m/Y') . ' (día lleno)';
                    continue;
                }
                
                // Verificar que no exista ya una asignación para ese día
                $exists = HomeOfficeAssignment::where('user_id', $targetUser->id)
                    ->whereDate('date', $date)
                    ->exists();
                    
                if ($exists) {
                    $skippedDates[] = $date->format('d/m/Y') . ' (ya asignado)';
                    continue;
                }
                
                // Crear la asignación
                HomeOfficeAssignment::create([
                    'user_id' => $targetUser->id,
                    'assigned_by' => $user->id,
                    'date' => $date,
                ]);
                
                $assignedDates[] = $date->format('d/m/Y');
                $assignedInThisMonth++;
            }
        }
        
        // Construir mensaje de respuesta
        $messages = [];
        
        if (count($assignedDates) > 0) {
            $messages[] = 'Días asignados: ' . implode(', ', $assignedDates);
        }
        
        if (count($skippedDates) > 0) {
            $messages[] = 'Días omitidos: ' . implode(', ', $skippedDates);
        }
        
        if (count($errors) > 0) {
            return back()->withErrors(['dates' => implode(' ', $errors)])
                ->with('success', count($assignedDates) > 0 ? implode('. ', $messages) : null);
        }
        
        if (count($assignedDates) === 0) {
            return back()->withErrors(['dates' => 'No se pudo asignar ninguna fecha. ' . implode(', ', $skippedDates)]);
        }
        
        return back()->with('success', implode('. ', $messages));
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
        $query = HomeOfficeAssignment::with(['user', 'assignedBy'])
            ->forMonth($month, $year);
            
        if (!$user->isAdmin()) {
            $query->whereHas('user', fn($q) => $q->where('work_area', $user->work_area));
        }
        
        $assignments = $query->orderBy('date')->get();
        
        // Crear contenido CSV con BOM para Excel
        $monthName = Carbon::create($year, $month, 1)->locale('es')->monthName;
        $filename = "home_office_{$monthName}_{$year}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($assignments, $monthName, $year) {
            $file = fopen('php://output', 'w');
            
            // BOM para UTF-8 en Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Encabezados
            fputcsv($file, [
                'Empleado',
                'Área',
                'Fecha',
                'Día',
                'Asignado por',
                'Fecha de asignación'
            ], ';');
            
            // Datos
            foreach ($assignments as $assignment) {
                fputcsv($file, [
                    $assignment->user->name . ' ' . ($assignment->user->last_name ?? ''),
                    $assignment->user->work_area ?? 'Sin área',
                    $assignment->date->format('d/m/Y'),
                    $assignment->date->locale('es')->dayName,
                    $assignment->assignedBy->name ?? 'Sistema',
                    $assignment->created_at->format('d/m/Y H:i')
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
