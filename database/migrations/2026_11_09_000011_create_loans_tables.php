<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: loans / deals ("qarza").
|
|   type 'borrowed' : the hotel owes someone.     type 'lent' : someone owes the hotel.
|   loan_repayments : each partial payment. Outstanding = amount - SUM(repayments). Status is derived:
|     0 repaid -> open,  some repaid -> partial,  nothing left -> settled   (see App\Models\Loan)
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            $table->enum('type', ['borrowed', 'lent']);
            $table->string('party');                       // person or company
            $table->decimal('amount', 14, 2);
            $table->date('issue_date');
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignUlid('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->date('paid_on');
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('loans');
    }
};
