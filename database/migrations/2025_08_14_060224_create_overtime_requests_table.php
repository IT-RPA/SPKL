<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('requester_employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->string('requester_level');
            $table->date('date');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            
            // ✅ PLANNING INTEGRATION
            $table->enum('overtime_category', ['planned', 'unplanned'])->default('unplanned')
                  ->comment('planned=dari planning, unplanned=lembur spontan');
            $table->foreignId('planning_id')->nullable()->constrained('overtime_plannings')->onDelete('set null');
            
            $table->enum('status', [
                'pending',
                'approved_sect',
                'approved_subdept',
                'approved_dept',
                'approved_subdiv',
                'approved_div',
                'approved_hrd',
                'approved',    // Semua approval selesai, bisa input data
                'rejected',
                'completed'    // Data actual/percentage sudah lengkap
            ])->default('pending');
            
            $table->string('status_color')->default('yellow');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};