<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: food menus and their RECIPES (two tables in one file because they belong together).
|
|   menus             : "Special Menu A", price per person.
|   menu_ingredients  : the recipe. "0.4 kg of Basmati rice per guest".
|
| The warehouse requisition (hawala) multiplies each recipe line by the guest count:
|   required quantity = per_person x guests          (see App\Services\RequisitionService)
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price_per_person', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('menu_ingredients', function (Blueprint $table) {
            $table->id(); // simple auto-increment id; nobody references it from outside
            $table->foreignUlid('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignUlid('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('per_person', 10, 4); // quantity per guest in the item's unit
            $table->timestamps();

            $table->unique(['menu_id', 'inventory_item_id']); // an ingredient appears once per menu
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_ingredients');
        Schema::dropIfExists('menus');
    }
};
