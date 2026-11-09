<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Partner { id, fullName, phone, email, address, investment, sharePercent, startDate, status, notes, transactions:[{date,type,amount,note}] } */
class PartnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'investment' => $this->investment,
            'sharePercent' => $this->share_percent,
            'startDate' => $this->start_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'transactions' => $this->transactions->map(fn ($t) => [
                'date' => $t->occurred_on->toDateString(), 'type' => $t->type, 'amount' => $t->amount, 'note' => $t->note,
            ])->values(),
        ];
    }
}
