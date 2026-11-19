<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: platform-wide settings, set by the Super Admin (name of the platform, support contacts, defaults).
|
| It is a tiny "key -> value" table instead of one column per setting, so adding a new setting later needs
| NO migration: just add it to App\Models\PlatformSetting::DEFAULTS.
|   key   : the setting name, e.g. 'supportEmail'   (the primary key, so each setting exists once)
|   value : the text value. The model converts numbers/booleans back from text.
|
| It has NO tenant_id on purpose: these settings belong to the platform, not to a hotel.
| (Migration basics: see 2026_11_09_000001_create_plans_table.php)
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            // `key` is a reserved word in MySQL, but Laravel quotes it for us, so it is safe here.
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
