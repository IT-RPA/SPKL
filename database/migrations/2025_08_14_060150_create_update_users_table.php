<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id')->unique()->after('id');
            $table->foreignId('role_id')->nullable()->constrained()->after('email_verified_at');
            $table->foreignId('department_id')->nullable()->constrained()->after('role_id');
            $table->enum('level', ['foreman', 'sect_head', 'dept_head', 'div_head', 'hrd'])->nullable()->after('department_id');
            $table->boolean('is_active')->default(true)->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['employee_id', 'role_id', 'department_id', 'level', 'is_active']);
        });
    }
};
