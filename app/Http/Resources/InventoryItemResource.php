<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** InventoryItem { id, name, category, unit, quantity, minQuantity, unitCost, spoiledQuantity, updatedAt } */
class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'minQuantity' => $this->min_quantity,
            'unitCost' => $this->unit_cost,
            'spoiledQuantity' => $this->spoiled_quantity,
            'updatedAt' => $this->updated_at?->toDateString(),
        ];
    }
}
