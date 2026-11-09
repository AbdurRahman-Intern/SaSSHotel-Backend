<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** LoanController  -  /api/loans (+ POST /api/loans/{id}/repayments). Supports ?type=borrowed|lent */
class LoanController extends TenantCrudController
{
    protected function modelClass(): string { return Loan::class; }
    protected function resourceClass(): string { return LoanResource::class; }
    protected function with(): array { return ['repayments']; }
    protected function filterable(): array { return ['type']; }
    protected function orderBy(): array { return ['due_date', 'asc']; }

    protected function rules(?string $id): array
    {
        return [
            'type' => ['required', Rule::in(['borrowed', 'lent'])],
            'party' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'issueDate' => ['required', 'date'],
            'dueDate' => ['required', 'date', 'after_or_equal:issueDate'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /** Record a (partial) repayment. Cannot pay more than what is still outstanding. */
    public function addRepayment(Request $request, string $id)
    {
        $loan = Loan::with('repayments')->findOrFail($id);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        if ($data['amount'] > $loan->outstanding()) {
            throw ValidationException::withMessages(['amount' => 'The repayment is larger than the outstanding amount.']);
        }

        LoanRepayment::create(['loan_id' => $loan->id, 'paid_on' => $data['date'], 'amount' => $data['amount']]);

        return new LoanResource($loan->fresh('repayments'));
    }
}
