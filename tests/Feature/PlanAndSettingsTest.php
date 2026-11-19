<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * PlanAndSettingsTest  -  checks the Super Admin "Plans" and "Settings" endpoints.
 * Run:  php artisan test --filter=PlanAndSettingsTest
 *
 * !! NOT RUN YET !! Written without a Laravel install. If a test fails, read the message, then decide whether the
 * test or the code is wrong. Keep these tests green: they protect who is allowed to change plans.
 */
class PlanAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@test.dev', 'password' => 'secret123', 'role' => 'super_admin']);
    }

    /** A hotel plus its owner, subscribed to the given plan. */
    private function ownerOnPlan(Plan $plan): User
    {
        $hotel = Hotel::create([
            'name' => 'H', 'slug' => 'h', 'owner_name' => 'O', 'owner_email' => 'o@test.dev', 'phone' => '1',
            'plan_id' => $plan->id, 'registered_at' => now()->toDateString(), 'currency' => 'AFN', 'contact_email' => 'o@test.dev',
        ]);

        return User::create(['name' => 'O', 'email' => 'o@test.dev', 'password' => 'secret123', 'role' => 'hotel_owner', 'tenant_id' => $hotel->id]);
    }

    public function test_super_admin_can_create_update_and_delete_a_plan(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $id = $this->postJson('/api/plans', ['name' => 'Gold Plus', 'priceMonthly' => 120, 'maxUsers' => 20])
            ->assertCreated()
            ->assertJsonPath('id', 'gold-plus')
            ->json('id');

        $this->patchJson("/api/plans/{$id}", ['priceMonthly' => 150])
            ->assertOk()
            ->assertJsonPath('priceMonthly', 150)
            ->assertJsonPath('name', 'Gold Plus'); // untouched fields stay

        $this->deleteJson("/api/plans/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('plans', ['id' => $id]);
    }

    public function test_hotel_owner_cannot_change_plans_but_can_list_them(): void
    {
        $plan = Plan::create(['id' => 'starter', 'name' => 'Starter', 'price_monthly' => 29, 'max_users' => 3]);
        Sanctum::actingAs($this->ownerOnPlan($plan));

        $this->getJson('/api/plans')->assertOk();
        $this->postJson('/api/plans', ['name' => 'Hack', 'priceMonthly' => 0, 'maxUsers' => 1])->assertForbidden();
        $this->deleteJson('/api/plans/starter')->assertForbidden();
    }

    public function test_a_plan_used_by_a_hotel_cannot_be_deleted(): void
    {
        $plan = Plan::create(['id' => 'starter', 'name' => 'Starter', 'price_monthly' => 29, 'max_users' => 3]);
        $this->ownerOnPlan($plan);
        Sanctum::actingAs($this->superAdmin());

        $this->deleteJson('/api/plans/starter')->assertStatus(422)->assertJsonValidationErrors('plan');
        $this->assertDatabaseHas('plans', ['id' => 'starter']);
    }

    public function test_plan_names_must_be_unique(): void
    {
        Plan::create(['id' => 'starter', 'name' => 'Starter', 'price_monthly' => 29, 'max_users' => 3]);
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/plans', ['name' => 'Starter', 'priceMonthly' => 1, 'maxUsers' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_settings_return_defaults_and_can_be_updated(): void
    {
        Plan::create(['id' => 'starter', 'name' => 'Starter', 'price_monthly' => 29, 'max_users' => 3]);
        Sanctum::actingAs($this->superAdmin());

        $this->getJson('/api/platform/settings')->assertOk()->assertJsonPath('trialDays', 14)->assertJsonPath('platformName', 'Hotel SaaS');

        $this->patchJson('/api/platform/settings', ['trialDays' => 30, 'supportEmail' => 'help@test.dev'])
            ->assertOk()
            ->assertJsonPath('trialDays', 30)           // came back as a NUMBER, not the text "30"
            ->assertJsonPath('supportEmail', 'help@test.dev')
            ->assertJsonPath('platformName', 'Hotel SaaS'); // unchanged settings keep their default

        // A default plan that does not exist is rejected.
        $this->patchJson('/api/platform/settings', ['defaultPlanId' => 'nope'])->assertStatus(422);
    }

    public function test_hotel_owner_cannot_read_platform_settings(): void
    {
        $plan = Plan::create(['id' => 'starter', 'name' => 'Starter', 'price_monthly' => 29, 'max_users' => 3]);
        Sanctum::actingAs($this->ownerOnPlan($plan));

        $this->getJson('/api/platform/settings')->assertForbidden();
    }
}
