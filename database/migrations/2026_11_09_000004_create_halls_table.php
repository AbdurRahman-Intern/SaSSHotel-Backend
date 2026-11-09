<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: wedding halls / salons of ONE hotel (e.g. "Grand Hall", capacity 400).
| Every tenant table below follows the same pattern:  ulid id  +  tenant_id foreign key  +  index on tenant_id.
| (Migration basics: see 2026_11_09_000001_create_plans_table.php)
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('halls', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // cascadeOnDelete = if a hotel row is ever deleted, its halls are deleted too.
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('capacity')->default(0);
            $table->timestamps();

            // A hotel cannot have two halls with the same name (but two hotels may).
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
