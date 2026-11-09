<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: warehouse items (rice, beef, cooking oil ...) and their CURRENT quantity.
|
| BUSINESS RULE: `quantity` must only change through stock_movements (stock-in, stock-out, spoilage),
| never by editing the item directly. See App\Services\StockService.
| `unit_cost` is the current market price per unit and follows the daily price (updated by purchases).
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('name');
            // Must match the React InventoryCategory type.
            $table->enum('category', ['Grains', 'Meat', 'Vegetables', 'Dairy', 'Spices', 'Oil', 'Beverages', 'Others']);
            $table->string('unit', 20);                                   // kg, liter, piece ...
            $table->decimal('quantity', 12, 2)->default(0);                // current stock. Never negative!
            $table->decimal('min_quantity', 12, 2)->default(0);            // below this => "low stock" alert
            $table->decimal('unit_cost', 12, 2)->default(0);               // price per unit
            $table->decimal('spoiled_quantity', 12, 2)->default(0);        // cumulative spoiled/damaged amount
            $table->timestamps();

            $table->index(['tenant_id', 'category']);
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
