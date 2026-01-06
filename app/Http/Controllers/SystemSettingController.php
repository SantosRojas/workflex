<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemSettingController extends Controller
{
    /**
     * Mostrar el formulario de configuración del sistema
     */
    public function index()
    {
        $user = Auth::user();
        
        // Solo administradores pueden acceder
        if (!$user->isAdmin()) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }
        
        $settings = [
            'max_home_office_days' => SystemSetting::getInt('max_home_office_days', 2),
            'max_people_per_day' => SystemSetting::getInt('max_people_per_day', 7),
            'daily_work_minutes' => SystemSetting::getInt('daily_work_minutes', 576),
        ];
        
        // Obtener historial de cambios
        $allSettings = SystemSetting::with('updatedBy')->get();
        
        return view('admin.settings', compact('settings', 'allSettings'));
    }

    /**
     * Actualizar la configuración del sistema
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        // Solo administradores pueden modificar
        if (!$user->isAdmin()) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }
        
        $request->validate([
            'max_home_office_days' => 'required|integer|min:1|max:30',
            'max_people_per_day' => 'required|integer|min:1|max:100',
            'daily_work_minutes' => 'required|integer|min:60|max:1440',
        ], [
            'max_home_office_days.required' => 'El número de días de home office es requerido.',
            'max_home_office_days.min' => 'El mínimo de días debe ser 1.',
            'max_home_office_days.max' => 'El máximo de días debe ser 30.',
            'max_people_per_day.required' => 'El número de personas por día es requerido.',
            'max_people_per_day.min' => 'El mínimo de personas debe ser 1.',
            'max_people_per_day.max' => 'El máximo de personas debe ser 100.',
            'daily_work_minutes.required' => 'Los minutos de trabajo son requeridos.',
            'daily_work_minutes.min' => 'El mínimo de minutos debe ser 60 (1 hora).',
            'daily_work_minutes.max' => 'El máximo de minutos debe ser 1440 (24 horas).',
        ]);
        
        SystemSetting::set('max_home_office_days', $request->max_home_office_days, $user->id);
        SystemSetting::set('max_people_per_day', $request->max_people_per_day, $user->id);
        SystemSetting::set('daily_work_minutes', $request->daily_work_minutes, $user->id);
        
        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
