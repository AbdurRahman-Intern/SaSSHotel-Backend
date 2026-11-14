<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PartnerResource;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * PartnerController  -  /api/partners  (+ POST /api/partners/{id}/transactions).
 * Remember: a partner WITHDRAWAL is not an expense. It only lives in partner_transactions.
 */
class PartnerController extends TenantCrudController
{
    protected function modelClass(): string { return Partner::class; }
    protected function resourceClass(): string { return PartnerResource::class; }
    protected function with(): array { return ['transactions']; }
    protected function orderBy(): array { return ['full_name', 'asc']; }

    protected function rules(?string $id): array
    {
        return [
            'fullName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:255'],
            'investment' => ['required', 'numeric', 'min:0'],
            'sharePercent' => ['required', 'numeric', 'between:0,100'],
            'startDate' => ['required', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ];
        // TODO: business rule - the total sharePercent of all active partners should not exceed 100.
    }

    protected function saved(Model $model, array $validated): void
    {
        if ($model->wasRecentlyCreated && $validated['investment'] > 0) {
            $model->transactions()->create([
                'type' => 'contribution',
                'amount' => $validated['investment'],
                'occurred_on' => $validated['startDate'],
                'note' => 'Initial investment',
            ]);
        }
    }

    /** POST /api/partners/{id}/transactions  { date, type, amount, note } -> returns the updated partner */
    public function addTransaction(Request $request, string $id)
    {
        $partner = Partner::findOrFail($id); // 404 if it is another hotel's partner

        $data = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', Rule::in(['contribution', 'profit_share', 'withdrawal'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        PartnerTransaction::create([
            'partner_id' => $partner->id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'occurred_on' => $data['date'],
            'note' => $data['note'] ?? '',
        ]);

        return new PartnerResource($partner->fresh('transactions'));
    }
}
