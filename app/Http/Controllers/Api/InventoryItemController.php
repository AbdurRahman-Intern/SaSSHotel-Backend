<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * InventoryItemController  -  /api/inventory-items.
 *
 * `quantity` may be sent ONLY when creating (the opening balance). After that it can change only through
 * StockMovementController (stock-in / stock-out / spoilage), never by PATCH.
 */
class InventoryItemController extends TenantCrudController
{
    protected function modelClass(): string { return InventoryItem::class; }
    protected function resourceClass(): string { return InventoryItemResource::class; }
    protected function filterable(): array { return ['category']; }
    protected function orderBy(): array { return ['name', 'asc']; }

    protected function rules(?string $id): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('inventory_items', 'name')->where('tenant_id', \App\Support\Tenancy::id())->ignore($id)],
            'category' => ['required', Rule::in(['Grains', 'Meat', 'Vegetables', 'Dairy', 'Spices', 'Oil', 'Beverages', 'Others'])],
            'unit' => ['required', 'string', 'max:20'],
            'quantity' => ['sometimes', 'numeric', 'min:0'], // opening balance, create only
            'minQuantity' => ['required', 'numeric', 'min:0'],
            'unitCost' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepare(array $data, ?Model $existing = null): array
    {
        if ($existing) {
            unset($data['quantity']); // ignore quantity on update (see class comment)
        }

        return $data;
    }

    /** Set the opening quantity before the response loads the new row. */
    protected function saved(Model $model, array $validated): void
    {
        if ($model->wasRecentlyCreated && isset($validated['quantity'])) {
            $model->quantity = $validated['quantity'];
            $model->save();
        }
    }
}
