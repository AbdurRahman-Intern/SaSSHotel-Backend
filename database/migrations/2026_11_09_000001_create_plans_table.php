<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| MIGRATIONS 101  (read this once, it applies to every migration file)
|--------------------------------------------------------------------------
| A migration is a PHP file that describes a change to the database.
| Instead of clicking around phpMyAdmin, we write the tables as code, so every teammate
| gets the same database by running one command:
|
|     php artisan migrate            -> runs every migration that has not run yet (calls up())
|     php artisan migrate:rollback   -> undoes the last batch                     (calls down())
|     php artisan migrate:fresh --seed -> DROPS everything, rebuilds, adds demo data (dev only!)
|
| RULES FOR TEAMMATES
|   - NEVER edit a migration that was already merged/run by someone else. Create a NEW migration
|     instead (php artisan make:migration add_something_to_bookings_table).
|   - File names start with a date so Laravel runs them in order. A table that is referenced
|     by a foreign key must be created BEFORE the table that references it.
|
| THIS FILE: the subscription plans (starter / professional / enterprise).
| Plans are labels only in the prototype; there is no billing engine yet (TODO later).
*/

return new class extends Migration
{
    /** up() = "apply this change". */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            // The plan id is a readable string ('starter') because the React app uses these exact ids.
            $table->string('id')->primary();
            $table->string('name');
            $table->decimal('price_monthly', 10, 2)->default(0); // money: ALWAYS decimal, never float
            $table->unsignedInteger('max_users')->default(1);
            $table->timestamps(); // created_at + updated_at, filled automatically by Eloquent
        });
    }

    /** down() = "undo this change" (used by migrate:rollback). */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
