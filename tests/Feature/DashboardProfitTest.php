<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExternalIncome;
use App\Models\Hotel;
use App\Models\InventoryItem;
use App\Models\Plan;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * DashboardProfitTest  -  profit / loss on the dashboard (income - expenses - salaries - stock purchases, negative = loss).
 * Run:  php artisan test --filter=DashboardProfitTest
 *
 * !! NOT RUN YET !! Written without a Laravel install; fix the test or the code if something fails.
 */
class DashboardProfitTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        Plan::firstOrCreate(['id' => 'starter'], ['name' => 'Starter', 'price_monthly' => 29, 'max_users' => 3]);
        $hotel = Hotel::create([
            'name' => 'H', 'slug' => 'h', 'owner_name' => 'O', 'owner_email' => 'o@test.dev', 'phone' => '1',
            'plan_id' => 'starter', 'registered_at' => now()->toDateString(), 'currency' => 'AFN', 'contact_email' => 'o@test.dev',
        ]);

        return User::create(['name' => 'O', 'email' => 'o@test.dev', 'password' => 'secret123', 'role' => 'hotel_owner', 'tenant_id' => $hotel->id]);
    }

    public function test_profit_is_income_minus_expenses_and_a_loss_is_negative(): void
    {
        $owner = $this->owner();
        $year = (int) now()->year;

        ExternalIncome::unguarded(fn () => ExternalIncome::create(['tenant_id' => $owner->tenant_id, 'received_on' => "$year-03-10", 'source' => 'Parking', 'description' => 'p', 'amount' => 500]));
        Expense::unguarded(fn () => Expense::create(['tenant_id' => $owner->tenant_id, 'spent_on' => "$year-03-12", 'category' => 'Rent', 'description' => 'r', 'amount' => 800]));
        Expense::unguarded(fn () => Expense::create(['tenant_id' => $owner->tenant_id, 'spent_on' => "$year-04-01", 'category' => 'Fuel', 'description' => 'f', 'amount' => 100]));

        // Stock: 100 kg bought at 3 in March = 300 cost. A stock-OUT must NOT count as a cost.
        $item = InventoryItem::unguarded(fn () => InventoryItem::create(['tenant_id' => $owner->tenant_id, 'name' => 'Rice', 'category' => 'Grains', 'unit' => 'kg', 'min_quantity' => 1, 'unit_cost' => 3]));
        StockMovement::unguarded(fn () => StockMovement::create(['tenant_id' => $owner->tenant_id, 'item_id' => $item->id, 'type' => 'in', 'quantity' => 100, 'unit_cost' => 3, 'moved_on' => "$year-03-05"]));
        StockMovement::unguarded(fn () => StockMovement::create(['tenant_id' => $owner->tenant_id, 'item_id' => $item->id, 'type' => 'out', 'quantity' => 50, 'unit_cost' => 3, 'moved_on' => "$year-03-06"]));

        Sanctum::actingAs($owner);
        $json = $this->getJson("/api/dashboard?year=$year")->assertOk()->json();

        $this->assertEquals(-700, $json['monthly'][2]['profit']);   // March: 500 - 800 - 300 stock = a loss
        $this->assertEquals(300, $json['monthly'][2]['stock']);
        $this->assertEquals(-100, $json['monthly'][3]['profit']);   // April: 0 - 100
        $this->assertEquals(-800, $json['yearProfit']);
        $this->assertEquals(300, $json['yearStock']);
        $this->assertEquals(900, $json['yearExpenses']);
        $this->assertEquals(0, $json['yearSalaries']);
    }
}
