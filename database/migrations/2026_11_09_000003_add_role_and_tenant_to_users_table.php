<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: extends the `users` table that Laravel already created for us
| (0001_01_01_000000_create_users_table.php) with the two columns our app needs.
|
|   role      : 'super_admin' (platform owner)  or  'hotel_owner' (runs ONE hotel).
|   tenant_id : which hotel a hotel_owner belongs to. NULL for the super admin.
|
| NOTE: users.id stays a normal auto-increment number (so Laravel Sanctum's token table works unchanged).
| The API converts it to a string in UserResource, which is what the React app expects.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'hotel_owner'])->default('hotel_owner')->after('email');
            $table->ulid('tenant_id')->nullable()->after('role');
            $table->foreign('tenant_id')->references('id')->on('hotels')->restrictOnDelete();
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'tenant_id']);
        });
    }
};
