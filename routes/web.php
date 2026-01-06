<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeOfficeController;
use App\Http\Controllers\FlexibleScheduleController;
use App\Http\Controllers\SystemSettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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
});

require __DIR__.'/auth.php';
