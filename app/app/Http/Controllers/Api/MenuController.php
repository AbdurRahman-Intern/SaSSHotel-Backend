<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * MenuController  -  /api/menus. A menu has a recipe (`ingredients`).
 * Example of the saved() hook: after the menu row is saved, we replace its child rows.
 */
class MenuController extends TenantCrudController
{
    protected function modelClass(): string { return Menu::class; }
    protected function resourceClass(): string { return MenuResource::class; }
    protected function with(): array { return ['ingredients']; }
    protected function orderBy(): array { return ['name', 'asc']; }

    protected function rules(?string $id): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('menus', 'name')->where('tenant_id', Tenancy::id())->ignore($id)],
            'pricePerPerson' => ['required', 'numeric', 'min:0'],
            'ingredients' => ['array'],
            'ingredients.*.inventoryItemId' => ['required', Tenancy::exists('inventory_items')],
            'ingredients.*.perPerson' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /** Replace the recipe lines with what the form sent (simple and safe for small lists). */
    protected function saved(Model $model, array $validated): void
    {
        if (! array_key_exists('ingredients', $validated)) {
            return; // PATCH without ingredients: leave the recipe alone
        }

        $model->ingredients()->delete();
        foreach ($validated['ingredients'] as $line) {
            $model->ingredients()->create([
                'inventory_item_id' => $line['inventoryItemId'],
                'per_person' => $line['perPerson'],
            ]);
        }
    }
}
