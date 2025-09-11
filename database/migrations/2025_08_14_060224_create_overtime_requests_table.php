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
            $table->foreignId('requester_id')->constrained('users');

            // kolom baru untuk relasi ke employees
            $table->foreignId('requester_employee_id')
                ->nullable()
                ->constrained('employees')
                ->onDelete('cascade');

            // requester_level sekarang pakai string
            $table->string('requester_level');

            $table->date('date');
            $table->foreignId('department_id')->constrained();
            $table->enum('status', [
                'pending',
                'approved_sect',
                'approved_subdept',
                'approved_dept',
                'approved_subdiv',
                'approved_div',
                'approved_hrd',
                'approved',
                'rejected',
                'completed'
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
