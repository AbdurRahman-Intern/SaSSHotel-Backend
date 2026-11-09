<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ExternalIncomeResource;
use App\Models\ExternalIncome;
use Illuminate\Validation\Rule;

/** ExternalIncomeController  -  /api/external-income (parking, recycling ... not from bookings). */
class ExternalIncomeController extends TenantCrudController
{
    protected function modelClass(): string { return ExternalIncome::class; }
    protected function resourceClass(): string { return ExternalIncomeResource::class; }
    protected function orderBy(): array { return ['received_on', 'desc']; }

    protected function rules(?string $id): array
    {
        return [
            'date' => ['required', 'date'],
            'source' => ['required', Rule::in(['Parking', 'Recycling', 'Other'])],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'receivedBy' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepare(array $data, ?\Illuminate\Database\Eloquent\Model $existing = null): array
    {
        if (array_key_exists('date', $data)) {
            $data['received_on'] = $data['date'];
            unset($data['date']);
        }

        return $data;
    }
}
