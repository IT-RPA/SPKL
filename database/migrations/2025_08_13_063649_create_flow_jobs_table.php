<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('flow_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_level_id')->constrained()->onDelete('cascade');

            // kolom baru langsung ditaruh di sini
            $table->foreignId('approver_employee_id')
                  ->nullable()
                  ->constrained('employees')
                  ->onDelete('cascade');

            $table->integer('step_order'); // urutan step dalam flow
            $table->string('step_name');   // nama step (misal: Pengajuan, Approval 1, dst)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Pastikan tidak ada duplikat step_order dalam satu department
            $table->unique(['department_id', 'step_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('flow_jobs');
    }
};
