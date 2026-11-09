<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Menu { id, name, pricePerPerson, ingredients: [{ inventoryItemId, perPerson }] } */
class MenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'pricePerPerson' => $this->price_per_person,
            'ingredients' => $this->ingredients->map(fn ($i) => [
                'inventoryItemId' => $i->inventory_item_id,
                'perPerson' => $i->per_person,
            ])->values(),
        ];
    }
}
