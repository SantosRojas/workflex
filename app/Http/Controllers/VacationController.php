<?php

namespace App\Http\Controllers;

use App\Models\VacationAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VacationController extends Controller
{
    /**
     * Mostrar la página principal de vacaciones
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Obtener año (por defecto el año actual)
        $year = $request->get('year', now()->year);
        
        // Verificar permisos
        if (!$user->canManageAssignments()) {
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'No tienes permisos para acceder a esta sección.']);
        }
        
        // Obtener usuarios según el rol
        if ($user->isAdmin()) {
            $teamMembers = User::orderBy('work_area')
                ->orderBy('name')
                ->get();
        } else {
            $teamMembers = User::where('work_area', $user->work_area)
                ->orderBy('name')
                ->get();
        }
        
        // Obtener asignaciones de vacaciones del año
        $assignmentsQuery = VacationAssignment::with(['user', 'assignedBy'])
            ->forYear($year);
        
        // Si es manager (no admin), filtrar solo por su área
        if (!$user->isAdmin()) {
            $assignmentsQuery->whereHas('user', fn($q) => $q->where('work_area', $user->work_area));
        }
        
        $assignments = $assignmentsQuery->orderBy('start_date')->get();
        
        // Agrupar asignaciones por usuario para mostrar resumen
        $userSummaries = [];
        foreach ($teamMembers as $member) {
            $userSummaries[$member->id] = VacationAssignment::getVacationSummary($member->id, $year);
        }
        
        // Años disponibles para el selector (año actual y siguiente)
        $availableYears = [
            now()->year,
            now()->year + 1,
        ];
        
        return view('vacation.index', compact(
            'assignments',
            'teamMembers',
            'year',
            'userSummaries',
            'availableYears'
        ));
    }

    /**
     * Almacenar una nueva asignación de vacaciones
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Validar permisos
        if (!$user->canManageAssignments()) {
            return back()->withErrors(['error' => 'No tienes permisos para realizar esta acción.']);
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'year' => 'required|integer|min:' . now()->year,
            'notes' => 'nullable|string|max:500',
        ], [
            'user_id.required' => 'Debes seleccionar un empleado.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.after_or_equal' => 'La fecha de inicio debe ser hoy o posterior.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ]);
        
        $targetUser = User::findOrFail($request->user_id);
        
        // Validar que el manager solo pueda asignar a usuarios de su área
        if (!$user->isAdmin() && $targetUser->work_area !== $user->work_area) {
            return back()->withErrors(['user_id' => 'Solo puedes asignar vacaciones a personas de tu área.']);
        }
        
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $year = (int) $request->year;
        
        // Calcular días efectivos considerando el límite de fines de semana
        $daysInfo = VacationAssignment::calculateEffectiveDays($targetUser->id, $year, $startDate, $endDate);
        $effectiveDays = $daysInfo['effective_days'];
        $calendarDays = $daysInfo['calendar_days'];
        
        // Verificar que no exceda el límite de 30 días
        if (!VacationAssignment::canAssignMoreDays($targetUser->id, $year, $effectiveDays)) {
            $available = VacationAssignment::getAvailableDays($targetUser->id, $year);
            return back()->withErrors([
                'dates' => "El empleado solo tiene {$available} días disponibles para el año {$year}. El período seleccionado consume {$effectiveDays} días efectivos."
            ]);
        }
        
        // Verificar que no haya solapamiento con vacaciones existentes
        if (VacationAssignment::hasOverlap($targetUser->id, $startDate, $endDate)) {
            return back()->withErrors([
                'dates' => 'El período seleccionado se solapa con vacaciones ya asignadas.'
            ]);
        }
        
        // Crear la asignación
        VacationAssignment::create([
            'user_id' => $targetUser->id,
            'assigned_by' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'calendar_days' => $calendarDays,
            'weekend_days' => $daysInfo['weekend_days_in_period'],
            'effective_days' => $effectiveDays,
            'year' => $year,
            'notes' => $request->notes,
        ]);
        
        $summary = VacationAssignment::getVacationSummary($targetUser->id, $year);
        
        // Mensaje detallado
        $weekendInfo = "";
        if ($daysInfo['weekend_days_to_count'] < $daysInfo['weekend_days_in_period']) {
            $notCounted = $daysInfo['weekend_days_in_period'] - $daysInfo['weekend_days_to_count'];
            $weekendInfo = " ({$notCounted} días de fin de semana no contados por límite alcanzado)";
        }
        
        return back()->with('success', 
            "Vacaciones asignadas: {$calendarDays} días calendario, {$effectiveDays} días efectivos{$weekendInfo}. " .
            "Días restantes: {$summary['available_days']} de " . VacationAssignment::TOTAL_VACATION_DAYS . "."
        );
    }

    /**
     * Actualizar una asignación de vacaciones existente
     */
    public function update(Request $request, VacationAssignment $vacation)
    {
        $user = Auth::user();
        
        // Validar permisos
        if (!$user->canManageAssignments()) {
            return back()->withErrors(['error' => 'No tienes permisos para realizar esta acción.']);
        }
        
        // Validar que el manager solo pueda editar asignaciones de su área
        if (!$user->isAdmin() && $vacation->user->work_area !== $user->work_area) {
            return back()->withErrors(['error' => 'Solo puedes editar vacaciones de personas de tu área.']);
        }
        
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:500',
        ]);
        
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        
        // Calcular días efectivos considerando el límite de fines de semana (excluyendo esta asignación)
        $daysInfo = VacationAssignment::calculateEffectiveDays($vacation->user_id, $vacation->year, $startDate, $endDate, $vacation->id);
        $effectiveDays = $daysInfo['effective_days'];
        $calendarDays = $daysInfo['calendar_days'];
        
        // Verificar límite
        $available = VacationAssignment::getAvailableDays($vacation->user_id, $vacation->year);
        // Sumar los días efectivos actuales de esta asignación para saber el disponible real
        $currentEffective = VacationAssignment::calculateEffectiveDays($vacation->user_id, $vacation->year, $vacation->start_date, $vacation->end_date, $vacation->id)['effective_days'];
        $realAvailable = $available + $currentEffective;
        
        if ($effectiveDays > $realAvailable) {
            return back()->withErrors([
                'dates' => "El empleado solo tiene {$available} días disponibles. El período seleccionado tiene {$calendarDays} días."
            ]);
        }
        
        // Verificar solapamiento (excluyendo la asignación actual)
        if (VacationAssignment::hasOverlap($vacation->user_id, $startDate, $endDate, $vacation->id)) {
            return back()->withErrors([
                'dates' => 'El período seleccionado se solapa con otras vacaciones ya asignadas.'
            ]);
        }
        
        $vacation->update([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'calendar_days' => $calendarDays,
            'weekend_days' => $daysInfo['weekend_days_in_period'],
            'effective_days' => $effectiveDays,
            'notes' => $request->notes,
        ]);
        
        return back()->with('success', 'Vacaciones actualizadas correctamente.');
    }

    /**
     * Eliminar una asignación de vacaciones
     */
    public function destroy(VacationAssignment $vacation)
    {
        $user = Auth::user();
        
        // Validar permisos
        if (!$user->canManageAssignments()) {
            return back()->withErrors(['error' => 'No tienes permisos para realizar esta acción.']);
        }
        
        // Validar que el manager solo pueda eliminar asignaciones de su área
        if (!$user->isAdmin() && $vacation->user->work_area !== $user->work_area) {
            return back()->withErrors(['error' => 'Solo puedes eliminar vacaciones de personas de tu área.']);
        }
        
        $vacation->delete();
        
        return back()->with('success', 'Asignación de vacaciones eliminada correctamente.');
    }

    /**
     * Ver reporte de vacaciones
     */
    public function report(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->canManageAssignments()) {
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'No tienes permisos para ver reportes.']);
        }
        
        $year = $request->get('year', now()->year);
        
        // Obtener usuarios según el rol
        if ($user->isAdmin()) {
            $teamMembers = User::orderBy('work_area')
                ->orderBy('name')
                ->get();
        } else {
            $teamMembers = User::where('work_area', $user->work_area)
                ->orderBy('name')
                ->get();
        }
        
        // Generar resúmenes para cada usuario
        $summaries = [];
        foreach ($teamMembers as $member) {
            $summaries[$member->id] = [
                'user' => $member,
                'summary' => VacationAssignment::getVacationSummary($member->id, $year),
            ];
        }
        
        // Años disponibles
        $availableYears = [
            now()->year - 1,
            now()->year,
            now()->year + 1,
        ];
        
        return view('vacation.report', compact(
            'summaries',
            'year',
            'availableYears'
        ));
    }

    /**
     * Exportar reporte a Excel
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->canManageAssignments()) {
            return back()->withErrors(['error' => 'No tienes permisos para exportar reportes.']);
        }
        
        $year = $request->get('year', now()->year);
        
        // Obtener usuarios según el rol
        if ($user->isAdmin()) {
            $teamMembers = User::orderBy('work_area')
                ->orderBy('name')
                ->get();
        } else {
            $teamMembers = User::where('work_area', $user->work_area)
                ->orderBy('name')
                ->get();
        }
        
        // Preparar datos para CSV
        $csvData = [];
        $csvData[] = ['Empleado', 'Área', 'Días Totales', 'Días Usados', 'Días Disponibles', 'Períodos'];
        
        foreach ($teamMembers as $member) {
            $summary = VacationAssignment::getVacationSummary($member->id, $year);
            
            $periods = $summary['assignments']->map(function ($a) {
                return $a->start_date->format('d/m') . ' - ' . $a->end_date->format('d/m');
            })->implode('; ');
            
            $csvData[] = [
                $member->name . ' ' . $member->last_name,
                $member->work_area,
                $summary['total_days'],
                $summary['used_days'],
                $summary['available_days'],
                $periods ?: 'Sin asignar',
            ];
        }
        
        // Generar CSV
        $filename = "vacaciones_{$year}.csv";
        $handle = fopen('php://temp', 'r+');
        
        // BOM para UTF-8 en Excel
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        foreach ($csvData as $row) {
            fputcsv($handle, $row, ';');
        }
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        
        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
