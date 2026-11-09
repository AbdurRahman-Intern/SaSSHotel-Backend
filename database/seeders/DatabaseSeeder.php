<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\Hotel;
use App\Models\InventoryItem;
use App\Models\Menu;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder  -  fills an EMPTY database with demo data:   php artisan migrate:fresh --seed
 *
 * Creates the SAME demo accounts as the React prototype (password = DEMO_PASSWORD in .env):
 *   admin@hotelsaas.demo    super admin
 *   owner@royal.demo        Royal Wedding Hall  (AFN)
 *   owner@kabulgrand.demo   Kabul Grand Hotel   (USD)
 *   owner@palace.demo       Palace (SUSPENDED, to test the suspended screen)
 *
 * SEEDERS HAVE NO LOGGED-IN USER, so BelongsToTenant cannot fill tenant_id for us:
 * we pass `tenant_id` explicitly (allowed here because run() calls Model::unguard()).
 *
 * NEVER run this against production data. Real hotels register through the Super Admin screen.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // `tenant_id` is deliberately NOT mass-assignable (security). Seeders are trusted code,
        // so we switch the protection off for the duration of this seeder and back on at the end.
        Model::unguard();

        $password = env('DEMO_PASSWORD', 'Demo@123456');

        // ---- Plans (ids match the React app) ----
        foreach ([
            ['starter', 'Starter', 29, 3],
            ['professional', 'Professional', 79, 10],
            ['enterprise', 'Enterprise', 199, 50],
        ] as [$id, $name, $price, $maxUsers]) {
            Plan::updateOrCreate(['id' => $id], ['name' => $name, 'price_monthly' => $price, 'max_users' => $maxUsers]);
        }

        // ---- Super admin ----
        User::updateOrCreate(['email' => 'admin@hotelsaas.demo'], [
            'name' => 'Platform Admin', 'password' => $password, 'role' => 'super_admin', 'tenant_id' => null,
        ]);

        // ---- Hotels + owners ----
        $royal = $this->hotel('Royal Wedding Hall', 'royal-wedding-hall', 'Ahmad Royal', 'owner@royal.demo', 'AFN', 'professional', 'active', $password);
        $kabul = $this->hotel('Kabul Grand Hotel', 'kabul-grand-hotel', 'Sami Grand', 'owner@kabulgrand.demo', 'USD', 'enterprise', 'active', $password);
        $this->hotel('Palace Hall', 'palace-hall', 'Palace Owner', 'owner@palace.demo', 'AFN', 'starter', 'suspended', $password);

        ActivityLog::record('Demo data seeded');

        // ---- Demo business data (a few rows each so every page has something to show) ----
        foreach ([$royal, $kabul] as $hotel) {
            $this->demoData($hotel);
        }

        // TODO (beginner exercise): add more demo rows - partners, loans, salary payments, stock movements.
        //   Pattern:  Model::create(['tenant_id' => $hotel->id, ...])

        Model::reguard();
    }

    private function hotel(string $name, string $slug, string $owner, string $email, string $currency, string $plan, string $status, string $password): Hotel
    {
        $hotel = Hotel::updateOrCreate(['slug' => $slug], [
            'name' => $name, 'owner_name' => $owner, 'owner_email' => $email, 'phone' => '+93 700 000 000',
            'address' => 'Kabul, Afghanistan', 'status' => $status, 'plan_id' => $plan,
            'registered_at' => now()->subMonths(6)->toDateString(), 'currency' => $currency, 'contact_email' => $email,
        ]);

        User::updateOrCreate(['email' => $email], [
            'name' => $owner, 'password' => $password, 'role' => 'hotel_owner', 'tenant_id' => $hotel->id,
        ]);

        return $hotel;
    }

    /** A hall, a few warehouse items, a menu, an employee, an expense and one booking. */
    private function demoData(Hotel $hotel): void
    {
        $t = ['tenant_id' => $hotel->id];

        Hall::create($t + ['name' => 'Grand Hall', 'capacity' => 600]);
        Hall::create($t + ['name' => 'Garden Hall', 'capacity' => 250]);

        $rice = new InventoryItem($t + ['name' => 'Basmati Rice', 'category' => 'Grains', 'unit' => 'kg', 'min_quantity' => 100, 'unit_cost' => 2.5]);
        $rice->tenant_id = $hotel->id;
        $rice->quantity = 500; // quantity is not fillable, so assign it directly
        $rice->save();

        $oil = new InventoryItem($t + ['name' => 'Cooking Oil', 'category' => 'Oil', 'unit' => 'liter', 'min_quantity' => 50, 'unit_cost' => 3]);
        $oil->tenant_id = $hotel->id;
        $oil->quantity = 40; // below minimum on purpose -> shows the low-stock alert
        $oil->save();

        $menu = new Menu(['name' => 'Special Menu A', 'price_per_person' => 12]);
        $menu->tenant_id = $hotel->id;
        $menu->save();
        $menu->ingredients()->create(['inventory_item_id' => $rice->id, 'per_person' => 0.4]);
        $menu->ingredients()->create(['inventory_item_id' => $oil->id, 'per_person' => 0.05]);

        $emp = new Employee(['full_name' => 'Karim Chef', 'phone' => '+93 700 111 222', 'position' => 'Head Chef', 'department' => 'Kitchen',
            'employee_type' => 'Full Time', 'gender' => 'Male', 'salary' => 600, 'join_date' => now()->subYear()->toDateString(),
            'status' => 'active', 'service_charge_eligible' => true]);
        $emp->tenant_id = $hotel->id;
        $emp->save();

        $exp = new Expense(['spent_on' => now()->toDateString(), 'category' => 'Fuel', 'description' => 'Generator fuel', 'amount' => 80, 'payment_method' => 'Cash']);
        $exp->tenant_id = $hotel->id;
        $exp->save();

        $b = new Booking(['client_name' => 'Ahmad Khan', 'client_phone' => '+93 700 333 444', 'event_type' => 'Wedding',
            'event_date' => now()->addDays(14)->toDateString(), 'shift' => 'Night', 'guest_count' => 400, 'hall_name' => 'Grand Hall',
            'menu_id' => $menu->id, 'food_price_per_person' => 12, 'service_charge' => 300, 'advance_paid' => 1000, 'status' => 'confirmed']);
        $b->tenant_id = $hotel->id;
        $b->reference = 'BK-' . now()->year . '-001';
        $b->save();
    }
}
