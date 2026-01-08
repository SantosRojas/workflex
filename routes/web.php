<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeOfficeController;
use App\Http\Controllers\FlexibleScheduleController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas de Home Office
    Route::get('/home-office', [HomeOfficeController::class, 'index'])->name('home-office.index');
    Route::post('/home-office', [HomeOfficeController::class, 'store'])->name('home-office.store');
    Route::delete('/home-office/{homeOffice}', [HomeOfficeController::class, 'destroy'])->name('home-office.destroy');
    Route::get('/home-office/report', [HomeOfficeController::class, 'report'])->name('home-office.report');

    // Rutas de Horario Flexible
    Route::get('/flexible-schedule', [FlexibleScheduleController::class, 'index'])->name('flexible-schedule.index');
    Route::post('/flexible-schedule', [FlexibleScheduleController::class, 'store'])->name('flexible-schedule.store');
    Route::put('/flexible-schedule/{flexibleSchedule}', [FlexibleScheduleController::class, 'update'])->name('flexible-schedule.update');
    Route::delete('/flexible-schedule/{flexibleSchedule}', [FlexibleScheduleController::class, 'destroy'])->name('flexible-schedule.destroy');
    Route::get('/flexible-schedule/report', [FlexibleScheduleController::class, 'report'])->name('flexible-schedule.report');

    // Rutas de Administración (solo admin)
    Route::get('/admin/settings', [SystemSettingController::class, 'index'])->name('admin.settings');
    Route::put('/admin/settings', [SystemSettingController::class, 'update'])->name('admin.settings.update');
    
    // Rutas de Gestión de Usuarios
    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/{user}/edit-password', [UserManagementController::class, 'editPassword'])->name('admin.users.edit-password');
    Route::put('/admin/users/{user}/update-password', [UserManagementController::class, 'updatePassword'])->name('admin.users.update-password');
    Route::get('/admin/users/{user}/edit-role', [UserManagementController::class, 'editRole'])->name('admin.users.edit-role');
    Route::put('/admin/users/{user}/update-role', [UserManagementController::class, 'updateRole'])->name('admin.users.update-role');
    Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
});

require __DIR__.'/auth.php';
