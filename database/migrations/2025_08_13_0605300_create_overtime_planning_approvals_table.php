<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_planning_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_id')->constrained('overtime_plannings')->onDelete('cascade');
            $table->foreignId('approver_employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('approver_level', 50);
            $table->integer('step_order');
            $table->string('step_name', 100);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index(['planning_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_planning_approvals');
    }
};
