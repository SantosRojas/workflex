<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    /**
     * Mostrar lista de usuarios con opciones de gestión
     */
    public function index(Request $request)
    {
        $admin = Auth::user();

        // Solo administradores pueden acceder
        if (!$admin->isAdmin()) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        // Búsqueda y filtrado
        $search = $request->get('search', '');
        $roleFilter = $request->get('role', '');

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($roleFilter, function ($query) use ($roleFilter) {
                return $query->where('role', $roleFilter);
            })
            ->orderBy('name')
            ->orderBy('last_name')
            ->paginate(15);

        $roles = ['admin', 'manager', 'user'];

        return view('admin.users.index', compact('users', 'search', 'roleFilter', 'roles'));
    }

    /**
     * Mostrar formulario para cambiar contraseña de un usuario
     */
    public function editPassword(User $user)
    {
        $admin = Auth::user();

        if (!$admin->isAdmin()) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        return view('admin.users.edit-password', compact('user'));
    }

    /**
     * Actualizar contraseña de un usuario
     */
    public function updatePassword(Request $request, User $user)
    {
        $admin = Auth::user();

        if (!$admin->isAdmin()) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $user->password = $validated['password'];
        $user->save();

        // Invalidar solicitudes de recuperación anteriores
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Contraseña actualizada para {$user->name} {$user->last_name}.");
    }

    /**
     * Mostrar formulario para editar rol de un usuario
     */
    public function editRole(User $user)
    {
        $admin = Auth::user();

        if (!$admin->isAdmin()) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        $roles = ['admin', 'manager', 'user'];

        return view('admin.users.edit-role', compact('user', 'roles'));
    }

    /**
     * Actualizar rol de un usuario
     */
    public function updateRole(Request $request, User $user)
    {
        $admin = Auth::user();

        if (!$admin->isAdmin()) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        // Prevenir que se elimine a todos los admins
        if ($user->role === 'admin' && $request->get('role') !== 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => 'Debe haber al menos un administrador.']);
            }
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,manager,user',
        ]);

        $user->role = $validated['role'];
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Rol actualizado para {$user->name} {$user->last_name}.");
    }

    /**
     * Mostrar formulario para editar área de un usuario
     */
    public function editArea(User $user)
    {
        $admin = Auth::user();

        if (!$admin->isAdmin()) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        return view('admin.users.edit-area', compact('user'));
    }

    /**
     * Actualizar área de un usuario
     */
    public function updateArea(Request $request, User $user)
    {
        $admin = Auth::user();

        if (!$admin->isAdmin()) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        $validated = $request->validate([
            'work_area' => 'required|string|max:255',
        ], [
            'work_area.required' => 'El área de trabajo es requerida.',
            'work_area.max' => 'El área de trabajo no puede tener más de 255 caracteres.',
        ]);

        $user->work_area = $validated['work_area'];
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Área actualizada para {$user->name} {$user->last_name}.");
    }

    /**
     * Eliminar (desactivar) un usuario
     */
    public function destroy(User $user)
    {
        $admin = Auth::user();

        if (!$admin->isAdmin()) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        // Prevenir autoeliminación
        if ($user->id === $admin->id) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        // Prevenir eliminar el último admin
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['error' => 'No se puede eliminar el único administrador.']);
            }
        }

        $userName = $user->name . ' ' . $user->last_name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuario {$userName} eliminado.");
    }
}
