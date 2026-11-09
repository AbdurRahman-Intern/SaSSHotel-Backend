<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| THIS FILE: the warehouse "ledger". One row per stock change.
|
|   type 'in'       = purchase (stock goes up, supplier + invoice number recorded)
|   type 'out'      = hawala / requisition issued for an event (stock goes down)
|   type 'spoilage' = damaged or spoiled goods (stock goes down)
|
| `quantity` is always POSITIVE; `type` says which direction it moves.
| A row is never edited later - mistakes are corrected with a new row (good accounting practice).
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->enum('type', ['in', 'out', 'spoilage']);
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 12, 2)->default(0);  // price per unit at the time of the movement
            $table->date('moved_on');
            $table->string('supplier')->nullable();            // stock-in only
            $table->string('reference')->default('');          // invoice no. (in) or "Wedding - Ahmad Khan" (out)
            $table->foreignUlid('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'type', 'moved_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
