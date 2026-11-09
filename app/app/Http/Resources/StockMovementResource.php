<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** StockMovement { id, itemId, type, quantity, unitCost, date, supplier, reference, bookingId, notes } */
class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'itemId' => $this->item_id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'unitCost' => $this->unit_cost,
            'date' => $this->moved_on?->toDateString(),
            'supplier' => $this->supplier,
            'reference' => $this->reference,
            'bookingId' => $this->booking_id,
            'notes' => $this->notes,
        ];
    }
}
