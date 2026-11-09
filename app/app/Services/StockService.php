<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * StockService  -  the ONLY place that changes warehouse quantities.
 *
 * WHY A SERVICE CLASS?
 *   "Put N kg into stock" touches two tables (inventory_items + stock_movements) and must be
 *   all-or-nothing. A service keeps that business logic out of controllers, so it can be reused
 *   (by the stock-out endpoint AND by the requisition endpoint) and tested alone.
 *
 * Every method runs inside DB::transaction(): if anything fails, nothing is saved.
 * lockForUpdate() makes a second request wait, so two people cannot take the same last 5 kg at once.
 */
class StockService
{
    /**
     * Purchase: stock goes UP and the item's market price is updated.
     *
     * @param  array  $data  itemId, quantity, unitCost, date, supplier, reference, notes
     */
    public function stockIn(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $item = InventoryItem::lockForUpdate()->findOrFail($data['itemId']);

            $item->quantity += $data['quantity'];
            $item->unit_cost = $data['unitCost']; // latest purchase price = current price
            $item->save();

            return $this->record($item, 'in', $data);
        });
    }

    /**
     * Issue goods for an event (hawala). Refuses if there is not enough stock.
     * Used by POST /stock-movements/out and by RequisitionService::issue().
     */
    public function stockOut(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $item = InventoryItem::lockForUpdate()->findOrFail($data['itemId']);
            $this->assertEnough($item, $data['quantity']);

            $item->quantity -= $data['quantity'];
            $item->save();

            return $this->record($item, 'out', $data);
        });
    }

    /** Damaged / expired goods: stock goes DOWN and spoiled_quantity goes UP. */
    public function spoilage(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $item = InventoryItem::lockForUpdate()->findOrFail($data['itemId']);
            $this->assertEnough($item, $data['quantity']);

            $item->quantity -= $data['quantity'];
            $item->spoiled_quantity += $data['quantity'];
            $item->save();

            return $this->record($item, 'spoilage', $data);
        });
    }

    /** Throws a 422 validation error (the React form shows it) when stock is too low. */
    private function assertEnough(InventoryItem $item, float $quantity): void
    {
        if ($quantity > $item->quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Not enough stock for {$item->name}. Available: {$item->quantity} {$item->unit}.",
            ]);
        }
    }

    /** Writes the ledger row. */
    private function record(InventoryItem $item, string $type, array $data): StockMovement
    {
        return StockMovement::create([
            'item_id' => $item->id,
            'type' => $type,
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unitCost'] ?? $item->unit_cost,
            'moved_on' => $data['date'] ?? now()->toDateString(),
            'supplier' => $data['supplier'] ?? null,
            'reference' => $data['reference'] ?? '',
            'booking_id' => $data['bookingId'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
