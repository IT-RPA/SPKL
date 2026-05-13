<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_plant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('plant_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Migrate existing plant_id data to the new pivot table
        $employees = DB::table('employees')->whereNotNull('plant_id')->get();
        foreach ($employees as $employee) {
            DB::table('employee_plant')->insert([
                'employee_id' => $employee->id,
                'plant_id' => $employee->plant_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_plant');
    }
};
