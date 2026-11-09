<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: staff and their monthly salary records.
|
|   employees.service_charge_eligible : only some employees receive a share of the service charge.
|   salary_payments : ONE row per employee per month (enforced by the unique index below).
|     net pay = basic_salary + service_charge - advance_deduction - other_deduction   (see App\Models\SalaryPayment)
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone', 40);
            $table->string('position');
            $table->string('department')->default('');
            $table->enum('employee_type', ['Full Time', 'Part Time', 'Contract'])->default('Full Time');
            $table->enum('gender', ['Male', 'Female']);
            $table->decimal('salary', 12, 2)->default(0);
            $table->date('join_date');
            $table->enum('status', ['active', 'on_leave', 'inactive'])->default('active');
            $table->boolean('service_charge_eligible')->default(false);
            $table->string('address')->default('');
            $table->string('id_number')->default('');          // national ID / tazkira number
            $table->string('emergency_contact')->default('');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('salary_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            // If an employee is deleted, their salary rows are deleted as well (the UI warns about this).
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->char('month', 7);                           // 'YYYY-MM', e.g. 2026-11
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->decimal('advance_deduction', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->enum('status', ['paid', 'pending'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'month']);           // no duplicate salary for the same month
            $table->index(['tenant_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('employees');
    }
};
