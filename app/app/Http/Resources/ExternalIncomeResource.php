<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** ExternalIncome { id, date, source, description, amount, receivedBy, notes } */
class ExternalIncomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->received_on?->toDateString(),
            'source' => $this->source,
            'description' => $this->description,
            'amount' => $this->amount,
            'receivedBy' => $this->received_by,
            'notes' => $this->notes,
        ];
    }
}
