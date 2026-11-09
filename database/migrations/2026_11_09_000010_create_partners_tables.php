<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: business partners and their account movements.
|
|   partner_transactions.type:
|     'contribution' : money the partner put into the business
|     'profit_share' : profit credited to the partner
|     'withdrawal'   : cash taken out by the partner
|
| IMPORTANT BUSINESS RULE: a withdrawal reduces the PARTNER'S account balance. It is NOT an operating
| expense and must never be inserted into the `expenses` table.
|   partner balance = SUM(profit_share) - SUM(withdrawal)     (see App\Models\Partner)
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone', 40);
            $table->string('email')->default('');
            $table->string('address')->default('');
            $table->decimal('investment', 14, 2)->default(0);
            $table->decimal('share_percent', 5, 2)->default(0); // 0 - 100
            $table->date('start_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignUlid('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->enum('type', ['contribution', 'profit_share', 'withdrawal']);
            $table->decimal('amount', 14, 2);
            $table->date('occurred_on');
            $table->string('note')->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_transactions');
        Schema::dropIfExists('partners');
    }
};
