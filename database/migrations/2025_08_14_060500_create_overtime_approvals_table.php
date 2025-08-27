<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_request_id')->constrained()->onDelete('cascade');

            // ganti approver_id -> approver_employee_id ke tabel employees
            $table->foreignId('approver_employee_id')->nullable()->constrained('employees')->onDelete('cascade');

            // approver_level jangan enum lagi, tapi string biar fleksibel
            $table->string('approver_level');

            // tambahan flow approval
            $table->integer('step_order');
            $table->string('step_name');

            $table->enum('status', ['pending', 'approved', 'rejected']);
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_approvals');
    }
};
