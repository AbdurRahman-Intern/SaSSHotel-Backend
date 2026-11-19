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
 * HotelManagementTest  -  Super Admin: suspend / activate / update / delete hotels and manage the owner login.
 * Run:  php artisan test --filter=HotelManagementTest
 *
 * !! NOT RUN YET !! Written without a Laravel install; fix the test or the code if something fails.
 */
class HotelManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@test.dev', 'password' => 'secret123', 'role' => 'super_admin']);
    }

    /** @return array{0: Hotel, 1: User} a hotel and its owner */
    private function hotelWithOwner(string $status = 'active'): array
    {
        Plan::firstOrCreate(['id' => 'starter'], ['name' => 'Starter', 'price_monthly' => 29, 'max_users' => 3]);
        $hotel = Hotel::create([
            'name' => 'Royal', 'slug' => 'royal', 'owner_name' => 'Ahmad', 'owner_email' => 'owner@test.dev', 'phone' => '1',
            'plan_id' => 'starter', 'registered_at' => now()->toDateString(), 'currency' => 'AFN', 'contact_email' => 'owner@test.dev', 'status' => $status,
        ]);
        $owner = User::create(['name' => 'Ahmad', 'email' => 'owner@test.dev', 'password' => 'secret123', 'role' => 'hotel_owner', 'tenant_id' => $hotel->id]);

        return [$hotel, $owner];
    }

    public function test_suspending_blocks_the_owner_immediately(): void
    {
        [$hotel, $owner] = $this->hotelWithOwner();

        Sanctum::actingAs($this->superAdmin());
        $this->patchJson("/api/hotels/{$hotel->id}", ['status' => 'suspended'])->assertOk()->assertJsonPath('status', 'suspended');

        Sanctum::actingAs($owner->fresh());
        $this->getJson('/api/halls')->assertForbidden()->assertJsonPath('code', 'hotel_suspended');

        Sanctum::actingAs($this->superAdmin());
        $this->patchJson("/api/hotels/{$hotel->id}", ['status' => 'active'])->assertOk()->assertJsonPath('status', 'active');
    }

    public function test_an_active_hotel_cannot_be_deleted(): void
    {
        [$hotel] = $this->hotelWithOwner('active');
        Sanctum::actingAs($this->superAdmin());

        $this->deleteJson("/api/hotels/{$hotel->id}")->assertStatus(422)->assertJsonValidationErrors('hotel');
        $this->assertDatabaseHas('hotels', ['id' => $hotel->id]);
    }

    public function test_a_suspended_hotel_is_deleted_with_its_owner_and_data(): void
    {
        [$hotel, $owner] = $this->hotelWithOwner('suspended');
        Hall::unguarded(fn () => Hall::create(['tenant_id' => $hotel->id, 'name' => 'Grand', 'capacity' => 100]));
        Sanctum::actingAs($this->superAdmin());

        $this->deleteJson("/api/hotels/{$hotel->id}")->assertNoContent();

        $this->assertDatabaseMissing('hotels', ['id' => $hotel->id]);
        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseMissing('halls', ['name' => 'Grand']); // removed by ON DELETE CASCADE
    }

    public function test_owner_name_and_email_can_be_changed_but_not_to_a_taken_email(): void
    {
        [$hotel, $owner] = $this->hotelWithOwner();
        User::create(['name' => 'Other', 'email' => 'taken@test.dev', 'password' => 'secret123', 'role' => 'super_admin']);
        Sanctum::actingAs($this->superAdmin());

        $this->patchJson("/api/hotels/{$hotel->id}/owner", ['ownerEmail' => 'taken@test.dev'])->assertStatus(422)->assertJsonValidationErrors('ownerEmail');

        $this->patchJson("/api/hotels/{$hotel->id}/owner", ['ownerName' => 'Ahmad Royal', 'ownerEmail' => 'new@test.dev'])
            ->assertOk()->assertJsonPath('ownerEmail', 'new@test.dev');

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'email' => 'new@test.dev', 'name' => 'Ahmad Royal']);
    }

    public function test_reset_password_returns_a_working_temporary_password(): void
    {
        [$hotel, $owner] = $this->hotelWithOwner();
        Sanctum::actingAs($this->superAdmin());

        $password = $this->postJson("/api/hotels/{$hotel->id}/owner/reset-password")->assertOk()->json('temporaryPassword');
        $this->assertNotEmpty($password);

        $this->postJson('/api/auth/login', ['email' => 'owner@test.dev', 'password' => $password])->assertOk()->assertJsonStructure(['token', 'user']);
        $this->postJson('/api/auth/login', ['email' => 'owner@test.dev', 'password' => 'secret123'])->assertStatus(422); // old one is dead
    }

    public function test_a_hotel_owner_cannot_manage_hotels(): void
    {
        [$hotel, $owner] = $this->hotelWithOwner();
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/hotels/{$hotel->id}")->assertForbidden();
        $this->postJson("/api/hotels/{$hotel->id}/owner/reset-password")->assertForbidden();
    }
}
