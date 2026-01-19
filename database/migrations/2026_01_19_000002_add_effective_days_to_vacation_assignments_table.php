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
        Schema::table('vacation_assignments', function (Blueprint $table) {
            $table->integer('weekend_days')->default(0)->after('calendar_days'); // Días de fin de semana en el período
            $table->integer('effective_days')->default(0)->after('weekend_days'); // Días efectivos descontados del saldo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacation_assignments', function (Blueprint $table) {
            $table->dropColumn(['weekend_days', 'effective_days']);
        });
    }
};
