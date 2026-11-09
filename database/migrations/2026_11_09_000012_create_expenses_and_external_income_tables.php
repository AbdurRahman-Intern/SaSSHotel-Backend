<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: two simple money-ledger tables.
|
|   expenses       : daily office costs (fuel, gas, electricity ...). These are OPERATING expenses.
|   external_income: income that is NOT a wedding booking (parking, selling recyclable waste ...).
|
| NOT expenses: partner withdrawals (see partner_transactions) and loan repayments (see loan_repayments).
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            $table->date('spent_on');
            $table->enum('category', ['Utilities', 'Fuel', 'Gas', 'Office Supplies', 'Maintenance', 'Internet', 'Transport', 'Rent', 'Miscellaneous']);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['Cash', 'Bank'])->default('Cash');
            $table->string('paid_by')->default('');
            $table->string('reference')->default('');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'spent_on']);
        });

        Schema::create('external_income', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            $table->date('received_on');
            $table->enum('source', ['Parking', 'Recycling', 'Other']);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('received_by')->default('');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'received_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_income');
        Schema::dropIfExists('expenses');
    }
};
