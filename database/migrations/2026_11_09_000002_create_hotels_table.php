<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: the hotels table. In this project  HOTEL == TENANT.
| Every business table created later has a `tenant_id` column that points to hotels.id.
| (New to migrations? Read the header of 2026_11_09_000001_create_plans_table.php first.)
|
| Columns match the React `Hotel` type (src/types/index.ts) after snake_case -> camelCase conversion.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            // ULID = a 26-character unique id such as 01HV3K...  Sortable by creation time, safe to expose in URLs,
            // and (unlike 1,2,3...) impossible to guess. The React app needs STRING ids, so we use ULIDs.
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('slug')->unique();       // URL-friendly unique name, e.g. "royal-wedding-hall"
            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('phone', 40);
            $table->string('address')->default('');
            $table->string('logo_url')->nullable(); // TODO: file upload (storage/app/public) in a later phase
            $table->enum('status', ['active', 'suspended'])->default('active');

            // Foreign key: must match a row in plans.id. restrictOnDelete = you cannot delete a plan that hotels use.
            $table->string('plan_id');
            $table->foreign('plan_id')->references('id')->on('plans')->restrictOnDelete();

            $table->date('registered_at');
            $table->char('currency', 3)->default('AFN'); // ISO 4217 code: AFN, USD, EUR
            $table->string('date_format', 20)->default('dd MMM yyyy'); // display preference only, dates are stored as real dates
            $table->string('contact_email');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
