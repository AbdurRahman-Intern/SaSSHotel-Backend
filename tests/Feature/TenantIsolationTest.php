<?php

namespace Tests\Feature;

use App\Models\Hall;
use App\Models\Hotel;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * TenantIsolationTest  -  proves Hotel A can NOT see or touch Hotel B's data.
 * Run:  php artisan test
 *
 * !! NOT RUN YET !! This file was written without a Laravel install available. If it fails, treat it as a
 * starting point: read the error, fix the test or (more importantly) the code, and keep this test green.
 * It is the most valuable test in the project. Add one like it for every new module.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase; // rebuilds the (test) database for every test

    private function makeHotelWithOwner(string $slug): array
    {
        Plan::firstOrCreate(['id' => 'starter'], ['name' => 'Starter', 'price_monthly' => 29, 'max_users' => 3]);
        $hotel = Hotel::create([
            'name' => $slug, 'slug' => $slug, 'owner_name' => 'Owner', 'owner_email' => "$slug@test.dev", 'phone' => '1',
            'plan_id' => 'starter', 'registered_at' => now()->toDateString(), 'currency' => 'AFN', 'contact_email' => "$slug@test.dev",
        ]);
        $user = User::create(['name' => 'Owner', 'email' => "$slug@test.dev", 'password' => 'secret123', 'role' => 'hotel_owner', 'tenant_id' => $hotel->id]);

        return [$hotel, $user];
    }

    public function test_owner_cannot_read_another_hotels_hall(): void
    {
        [$hotelA, $ownerA] = $this->makeHotelWithOwner('hotel-a');
        [$hotelB] = $this->makeHotelWithOwner('hotel-b');
        // tenant_id is not mass-assignable, so create the row 'unguarded' (test code only).
        $hallOfB = Hall::unguarded(fn () => Hall::create(['tenant_id' => $hotelB->id, 'name' => 'Secret Hall', 'capacity' => 100]));

        Sanctum::actingAs($ownerA);

        $this->getJson('/api/halls')->assertOk()->assertJsonMissing(['name' => 'Secret Hall']);
        $this->getJson("/api/halls/{$hallOfB->id}")->assertNotFound();      // 404, not 403: do not reveal it exists
        $this->deleteJson("/api/halls/{$hallOfB->id}")->assertNotFound();
    }

    public function test_tenant_id_in_request_body_is_ignored(): void
    {
        [$hotelA, $ownerA] = $this->makeHotelWithOwner('hotel-a');
        [$hotelB] = $this->makeHotelWithOwner('hotel-b');

        Sanctum::actingAs($ownerA);
        $this->postJson('/api/halls', ['name' => 'Mine', 'capacity' => 50, 'tenant_id' => $hotelB->id])->assertCreated();

        $this->assertSame($hotelA->id, Hall::withoutTenant()->where('name', 'Mine')->first()->tenant_id);
    }

    public function test_hotel_owner_cannot_use_super_admin_routes(): void
    {
        [, $owner] = $this->makeHotelWithOwner('hotel-a');
        Sanctum::actingAs($owner);

        $this->getJson('/api/hotels')->assertForbidden();
    }
}
