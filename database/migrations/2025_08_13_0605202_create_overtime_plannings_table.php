<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_plannings', function (Blueprint $table) {
            $table->id();
            $table->string('planning_number', 50)->unique();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->date('planned_date')->comment('Tanggal spesifik lembur akan dilaksanakan');
            
            // Planning Details
            $table->integer('max_employees')->comment('Kuota maksimal karyawan');
            $table->time('planned_start_time');
            $table->time('planned_end_time');
            $table->text('work_description');
            $table->text('reason');
            
            // Tracking Usage
            $table->integer('used_employees')->default(0);
            $table->integer('remaining_employees')->comment('Sisa kuota');
            
            // Status Flow
            $table->enum('status', [
                'draft',      // Baru dibuat, belum submit
                'pending',    // Submit, menunggu approval
                'approved',   // Sudah diapprove semua
                'rejected',   // Ditolak
                'active',     // Sudah H-nya, bisa digunakan
                'completed',  // Quota habis atau sudah melewati tanggal
                'expired'     // Melewati H+1 tapi quota belum habis
            ])->default('draft');
            
            // Audit Trail
            $table->foreignId('created_by')->constrained('employees')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('employees')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            
            // Indexes untuk performance
            $table->index(['department_id', 'planned_date']);
            $table->index('status');
            $table->index('planned_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_plannings');
    }
};
