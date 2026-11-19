<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * MenuController  -  /api/menus: the food menus a hotel offers, each with a price per person and a RECIPE.
 *
 * HOW MENUS ARE USED
 *   - On a booking the owner picks a menu "according to the customer's desire". The booking copies the menu's
 *     price per person (and the owner may still change it for that customer).
 *   - The warehouse requisition (hawala) multiplies the recipe by the guest count:  perPerson x guests.
 *   - To make a special menu for one customer, the React app opens the menu form pre-filled from an existing menu
 *     and saves it as a NEW menu. There is no special "duplicate" endpoint: it is just POST /menus.
 *
 * IMPORTANT: editing a menu's recipe changes the hawala of EVERY booking that uses it. The React app warns about this.
 *
 * Example of the saved() hook: after the menu row is saved, we replace its child rows (the recipe).
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
            // A menu without a recipe is useless for the hawala, so at least one ingredient is required.
            'ingredients' => ['required', 'array', 'min:1'],
            // `distinct` = the same warehouse item can not appear twice in one recipe.
            'ingredients.*.inventoryItemId' => ['required', 'distinct', Tenancy::exists('inventory_items')],
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

    /**
     * DELETE /api/menus/{id}
     *
     * Refused (422) while any booking uses the menu. Without this check the database would quietly set the
     * booking's menu to NULL and the booking would lose its hawala. Edit the menu or make a new one instead.
     */
    public function destroy(string $id): JsonResponse|Response
    {
        $menu = $this->query()->findOrFail($id);

        $count = $menu->bookings()->count();
        if ($count > 0) {
            throw ValidationException::withMessages([
                'menu' => "This menu is used by {$count} booking(s) and can not be deleted.",
            ]);
        }

        $menu->delete();

        return response()->noContent();
    }
}
