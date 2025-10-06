<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_level_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->integer('step_order');
            $table->string('step_name');
            $table->enum('applies_to', ['planned', 'unplanned', 'both'])->default('both')
                  ->comment('planned=planning lembur, unplanned=overtime biasa, both=keduanya');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['department_id', 'step_order', 'applies_to'], 'unique_dept_step_applies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_jobs');
    }
};