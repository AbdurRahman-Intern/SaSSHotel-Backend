<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Validation\Rule;

/** ExpenseController  -  /api/expenses (daily operating costs). Supports ?category=Fuel */
class ExpenseController extends TenantCrudController
{
    protected function modelClass(): string { return Expense::class; }
    protected function resourceClass(): string { return ExpenseResource::class; }
    protected function filterable(): array { return ['category']; }
    protected function orderBy(): array { return ['spent_on', 'desc']; }

    protected function rules(?string $id): array
    {
        return [
            'date' => ['required', 'date'],
            'category' => ['required', Rule::in(['Utilities', 'Fuel', 'Gas', 'Office Supplies', 'Maintenance', 'Internet', 'Transport', 'Rent', 'Miscellaneous'])],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'paymentMethod' => ['required', Rule::in(['Cash', 'Bank'])],
            'paidBy' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /** The API says `date`, the column is `spent_on`. Rename it here. */
    protected function prepare(array $data, ?\Illuminate\Database\Eloquent\Model $existing = null): array
    {
        if (array_key_exists('date', $data)) {
            $data['spent_on'] = $data['date'];
            unset($data['date']);
        }

        return $data;
    }
}
