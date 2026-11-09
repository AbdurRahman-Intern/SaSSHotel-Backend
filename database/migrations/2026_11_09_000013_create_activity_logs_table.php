<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: a simple platform-level activity feed shown on the Super Admin dashboard
| ("New hotel registered: ...", "Hotel suspended: ...").
| It has NO tenant_id on purpose: it belongs to the platform, not to a single hotel.
| TODO (later): add the acting user id, and also log important hotel-level actions for auditing.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('message');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
