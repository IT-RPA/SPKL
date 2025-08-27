<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_request_id')->constrained()->onDelete('cascade');

            // ubah foreign key employee_id ke tabel employees
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('cascade');

            $table->time('start_time');
            $table->time('end_time');
            $table->text('work_priority');
            $table->text('work_process');
            $table->integer('qty_plan')->nullable();
            $table->integer('qty_actual')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_actual_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_details');
    }
};
