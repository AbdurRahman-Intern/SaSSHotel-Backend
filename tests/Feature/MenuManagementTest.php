<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Hotel;
use App\Models\InventoryItem;
use App\Models\Menu;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * MenuManagementTest  -  creating menus with a recipe and using them on bookings.
 * Run:  php artisan test --filter=MenuManagementTest
 *
 * !! NOT RUN YET !! Written without a Laravel install; fix the test or the code if something fails.
 */
class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;

    /** Logs in a hotel owner and creates two warehouse items (rice, oil) for them. @return array{0: User, 1: string, 2: string} */
    private function ownerWithItems(string $slug = 'a'): array
    {
        Plan::firstOrCreate(['id' => 'starter'], ['name' => 'Starter', 'price_monthly' => 29, 'max_users' => 3]);
        $this->hotel = Hotel::create([
            'name' => $slug, 'slug' => $slug, 'owner_name' => 'O', 'owner_email' => "$slug@test.dev", 'phone' => '1',
            'plan_id' => 'starter', 'registered_at' => now()->toDateString(), 'currency' => 'AFN', 'contact_email' => "$slug@test.dev",
        ]);
        $owner = User::create(['name' => 'O', 'email' => "$slug@test.dev", 'password' => 'secret123', 'role' => 'hotel_owner', 'tenant_id' => $this->hotel->id]);

        $rice = InventoryItem::unguarded(fn () => InventoryItem::create(['tenant_id' => $this->hotel->id, 'name' => "Rice $slug", 'category' => 'Grains', 'unit' => 'kg', 'min_quantity' => 1, 'unit_cost' => 2]));
        $oil = InventoryItem::unguarded(fn () => InventoryItem::create(['tenant_id' => $this->hotel->id, 'name' => "Oil $slug", 'category' => 'Oil', 'unit' => 'liter', 'min_quantity' => 1, 'unit_cost' => 3]));

        return [$owner, $rice->id, $oil->id];
    }

    public function test_owner_can_create_a_menu_with_a_recipe_and_read_it_back(): void
    {
        [$owner, $rice, $oil] = $this->ownerWithItems();
        Sanctum::actingAs($owner);

        $id = $this->postJson('/api/menus', [
            'name' => 'Wedding Special', 'pricePerPerson' => 900,
            'ingredients' => [['inventoryItemId' => $rice, 'perPerson' => 0.4], ['inventoryItemId' => $oil, 'perPerson' => 0.05]],
        ])->assertCreated()->assertJsonCount(2, 'ingredients')->json('id');

        $this->getJson("/api/menus/{$id}")->assertOk()->assertJsonPath('name', 'Wedding Special')->assertJsonPath('pricePerPerson', 900);

        // Update: replace the recipe with a single ingredient.
        $this->patchJson("/api/menus/{$id}", ['ingredients' => [['inventoryItemId' => $rice, 'perPerson' => 0.5]]])
            ->assertOk()->assertJsonCount(1, 'ingredients');
    }

    public function test_a_menu_needs_ingredients_and_no_duplicate_items(): void
    {
        [$owner, $rice] = $this->ownerWithItems();
        Sanctum::actingAs($owner);

        $this->postJson('/api/menus', ['name' => 'Empty', 'pricePerPerson' => 10, 'ingredients' => []])->assertStatus(422)->assertJsonValidationErrors('ingredients');

        $this->postJson('/api/menus', [
            'name' => 'Twice', 'pricePerPerson' => 10,
            'ingredients' => [['inventoryItemId' => $rice, 'perPerson' => 1], ['inventoryItemId' => $rice, 'perPerson' => 2]],
        ])->assertStatus(422);
    }

    public function test_a_menu_can_not_use_another_hotels_warehouse_item(): void
    {
        [, $riceOfA] = $this->ownerWithItems('a');
        [$ownerB] = $this->ownerWithItems('b');
        Sanctum::actingAs($ownerB);

        $this->postJson('/api/menus', ['name' => 'Sneaky', 'pricePerPerson' => 10, 'ingredients' => [['inventoryItemId' => $riceOfA, 'perPerson' => 1]]])
            ->assertStatus(422)->assertJsonValidationErrors('ingredients.0.inventoryItemId');
    }

    public function test_a_menu_used_by_a_booking_can_not_be_deleted_but_an_unused_one_can(): void
    {
        [$owner, $rice] = $this->ownerWithItems();
        Sanctum::actingAs($owner);

        $used = $this->postJson('/api/menus', ['name' => 'Used', 'pricePerPerson' => 10, 'ingredients' => [['inventoryItemId' => $rice, 'perPerson' => 1]]])->json('id');
        $free = $this->postJson('/api/menus', ['name' => 'Free', 'pricePerPerson' => 10, 'ingredients' => [['inventoryItemId' => $rice, 'perPerson' => 1]]])->json('id');

        Hall::unguarded(fn () => Hall::create(['tenant_id' => $this->hotel->id, 'name' => 'Grand', 'capacity' => 100]));
        $this->postJson('/api/bookings', [
            'clientName' => 'Ahmad', 'clientPhone' => '1', 'eventType' => 'Wedding', 'eventDate' => now()->addMonth()->toDateString(), 'shift' => 'Night',
            'guestCount' => 100, 'hallName' => 'Grand', 'menuId' => $used, 'foodPricePerPerson' => 10, 'status' => 'pending',
        ])->assertCreated();

        $this->deleteJson("/api/menus/{$used}")->assertStatus(422)->assertJsonValidationErrors('menu');
        $this->assertTrue(Menu::query()->whereKey($used)->exists());

        $this->deleteJson("/api/menus/{$free}")->assertNoContent();
    }
}
