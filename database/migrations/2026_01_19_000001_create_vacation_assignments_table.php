<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vacation_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('no action');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('calendar_days'); // Días calendario del periodo (incluyendo fines de semana)
            $table->integer('year'); // Año fiscal de las vacaciones
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índice para búsquedas por usuario y año
            $table->index(['user_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacation_assignments');
    }
};
