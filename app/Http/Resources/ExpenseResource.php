<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Expense { id, date, category, description, amount, paymentMethod, paidBy, reference, notes } */
class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->spent_on?->toDateString(),
            'category' => $this->category,
            'description' => $this->description,
            'amount' => $this->amount,
            'paymentMethod' => $this->payment_method,
            'paidBy' => $this->paid_by,
            'reference' => $this->reference,
            'notes' => $this->notes,
        ];
    }
}
