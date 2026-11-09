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

    /** `quantity` is not in $fillable (protected), so set it explicitly for new rows. */
    public function store(\Illuminate\Http\Request $request)
    {
        $response = parent::store($request);

        if ($request->filled('quantity')) {
            InventoryItem::where('id', $response->getData()->id)->update(['quantity' => $request->input('quantity')]);
            // TODO (nicer): record the opening balance as a stock-in movement so the ledger adds up.
        }

        return $response;
    }
}
