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
        Schema::table('flexible_schedule_assignments', function (Blueprint $table) {
            //agregagamos hora de almuerzo
            $table->time('lunch_start_time')->nullable()->after('date')->default('12:00:00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flexible_schedule_assignments', function (Blueprint $table) {
            $table->dropColumn('lunch_start_time');
        });
    }
};
