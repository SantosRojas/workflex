<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Asignaciones de home office del usuario
     */
    public function homeOfficeAssignments()
    {
        return $this->hasMany(HomeOfficeAssignment::class);
    }

    /**
     * Asignaciones de horario flexible del usuario
     */
    public function flexibleScheduleAssignments()
    {
        return $this->hasMany(FlexibleScheduleAssignment::class);
    }

    /**
     * Usuarios del mismo área de trabajo (para managers)
     */
    public function teamMembers()
    {
        return $this->where('work_area', $this->work_area)
                    ->where('id', '!=', $this->id);
    }

    /**
     * Verificar si el usuario es manager
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Verificar si el usuario es admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Verificar si puede gestionar asignaciones
     */
    public function canManageAssignments(): bool
    {
        return $this->isManager() || $this->isAdmin();
    }

    /**
     * Obtener los días de home office usados en un mes específico
     */
    public function homeOfficeDaysInMonth(int $month, int $year): int
    {
        return $this->homeOfficeAssignments()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->count();
    }
}
