<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Loan { id, type, party, amount, issueDate, dueDate, repayments:[{date,amount}], notes } */
class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'party' => $this->party,
            'amount' => $this->amount,
            'issueDate' => $this->issue_date?->toDateString(),
            'dueDate' => $this->due_date?->toDateString(),
            'repayments' => $this->repayments->map(fn ($r) => ['date' => $r->paid_on->toDateString(), 'amount' => $r->amount])->values(),
            'notes' => $this->notes,
        ];
    }
}
