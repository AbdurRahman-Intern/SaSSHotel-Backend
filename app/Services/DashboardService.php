<?php

namespace App\Services;

use App\Http\Resources\BookingResource;
use App\Http\Resources\InventoryItemResource;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExternalIncome;
use App\Models\InventoryItem;
use App\Models\Loan;
use App\Models\SalaryPayment;
use Illuminate\Support\Collection;

/**
 * DashboardService  -  calculates every number on the hotel owner's dashboard.
 *
 * WHERE IT IS USED
 *   DashboardController (GET /api/dashboard?year=2026)  ->  DashboardService::build($year)  ->  JSON.
 *   The React page shows the result directly (type `DashboardStatistics` in src/types/index.ts).
 *
 * THIS IS A PORT OF src/services/dashboard.service.ts
 *   The React prototype calculated the dashboard in the browser. This class does the same maths on the
 *   server, so both give IDENTICAL numbers. If you change a rule here, change it in the other file too,
 *   until the React version is removed.
 *
 * TENANT SAFETY
 *   Every model used here (Booking, Expense ...) has the BelongsToTenant trait, so each query is
 *   automatically limited to the logged-in hotel. This class never mentions a tenant id.
 *
 * KEY DEFINITIONS (write them down, accountants will ask)
 *   "live" booking     = status pending, confirmed or completed. Draft and cancelled bookings do NOT count.
 *   "income"           = CASH RECEIVED on bookings (advance + later payments) + external income (parking ...).
 *                        It is NOT the contract value. Contract value ("booked revenue") belongs in Reports.
 *   "expenses"         = rows of the expenses table only. Salaries are reported separately.
 *   NOT counted anywhere: partner withdrawals and loan repayments (they are not operating costs).
 *
 * PERFORMANCE NOTE
 *   For clarity we load the rows into PHP "Collections" and calculate there (easy to read and debug).
 *   That is fine for one hotel's data (thousands of rows). If it ever becomes slow, replace a method with a SQL
 *   query that uses SUM() / GROUP BY. The public output stays the same, so nothing else has to change.
 */
class DashboardService
{
    /**
     * Builds the whole dashboard.
     *
     * @param  int  $year  the year of the yearly numbers and the chart (default: this year)
     * @return array<string, mixed>  keys exactly match DashboardStatistics in the React types
     */
    public function build(?int $year = null): array
    {
        $year ??= (int) now()->year;
        $today = now()->toDateString();          // '2026-11-11'
        $thisMonth = now()->format('Y-m');       // '2026-11'

        // ---- 1. Load the data once --------------------------------------------------------------
        // with('payments') loads all payments in ONE extra query instead of one query per booking
        // (avoids the "N+1 query" problem).
        $bookings = Booking::with('payments')->get();
        $expenses = Expense::all();
        $income = ExternalIncome::all();
        $items = InventoryItem::all();
        $loans = Loan::with('repayments')->get();

        // Live bookings are used by almost every number below.
        $live = $bookings->filter(fn (Booking $b) => $this->isLive($b))->values();

        // ---- 2. The 12-month series for the chart ------------------------------------------------
        $monthly = $this->monthlySeries($year, $live, $income, $expenses);

        // The current month's row. If the selected year is not the current year, this month is not in the
        // series, so the month numbers are 0 (same behaviour as the React version).
        $monthRow = $monthly->firstWhere('month', $thisMonth) ?? ['income' => 0, 'expenses' => 0, 'weddings' => 0];

        // ---- 3. Upcoming events ------------------------------------------------------------------
        $upcoming = $live
            ->filter(fn (Booking $b) => $b->event_date->toDateString() >= $today)
            ->sortBy(fn (Booking $b) => $b->event_date->toDateString())
            ->values();

        // ---- 4. Loans ----------------------------------------------------------------------------
        // "Given" = money we lent and have not got back.  "Borrowed" = money we owe and have not paid back.
        $totalLoanGiven = $loans->where('type', 'lent')->sum(fn (Loan $l) => $l->outstanding());
        $totalBorrowed = $loans->where('type', 'borrowed')->sum(fn (Loan $l) => $l->outstanding());

        // ---- 5. Today's small profit figure ------------------------------------------------------
        // Matches the prototype: today's external income minus today's expenses.
        // TODO (suggested improvement): also add cash received on bookings today (payments dated today).
        $dayIncome = $income->filter(fn ($x) => $x->received_on->toDateString() === $today)->sum('amount');
        $dayExpense = $expenses->filter(fn ($x) => $x->spent_on->toDateString() === $today)->sum('amount');

        // ---- 6. Assemble the answer --------------------------------------------------------------
        return [
            'dailyNetProfit' => $this->money($dayIncome - $dayExpense),

            'monthIncome' => $monthRow['income'],
            'yearIncome' => $this->money($monthly->sum('income')),
            'monthExpenses' => $monthRow['expenses'],
            'yearExpenses' => $this->money($monthly->sum('expenses')),
            'monthWeddings' => $monthRow['weddings'],
            'yearWeddings' => (int) $monthly->sum('weddings'),

            'totalLoanGiven' => $this->money($totalLoanGiven),
            'totalBorrowed' => $this->money($totalBorrowed),

            // Value of everything in the warehouse = quantity x price per unit, per item.
            'stockValue' => $this->money($items->sum(fn (InventoryItem $i) => round($i->quantity * $i->unit_cost, 2))),

            // Staff who are active or on leave (everyone except "inactive").
            'totalEmployees' => Employee::where('status', '!=', 'inactive')->count(),

            // Salaries marked "paid" for the CURRENT month (net pay = basic + service charge - deductions).
            'salaryPaidThisMonth' => $this->money(
                SalaryPayment::where('status', 'paid')->where('month', $thisMonth)->get()->sum(fn (SalaryPayment $s) => $s->net())
            ),

            'monthly' => $monthly->all(),

            // The 5 newest bookings of ANY status (so a draft that was just created shows up).
            // ->resolve() turns the resource into a plain array so it can sit inside this bigger array.
            'recentBookings' => BookingResource::collection($bookings->sortByDesc('created_at')->take(5)->values())->resolve(),

            // The first 5 items that are low or out of stock.
            'lowStock' => InventoryItemResource::collection($items->filter(fn (InventoryItem $i) => $this->stockStatus($i) !== 'in_stock')->take(5)->values())->resolve(),

            'upcoming' => BookingResource::collection($upcoming->take(5))->resolve(),

            // How many live bookings per event type in the selected year (for the doughnut chart).
            'eventDistribution' => $this->eventDistribution($live, $year),

            // Money live bookings still owe us (never below 0 per booking).
            'pendingPayments' => $this->money($live->sum(fn (Booking $b) => $b->remaining())),

            // Bookings still waiting for confirmation (any date).
            'pendingBookings' => $bookings->where('status', 'pending')->count(),

            // Total guests expected at ALL upcoming events (not only the 5 shown above).
            'upcomingGuests' => (int) $upcoming->sum('guest_count'),

            'todayBookings' => $live->filter(fn (Booking $b) => $b->event_date->toDateString() === $today)->count(),
        ];
    }

    // =============================================================================================
    // Helper methods (private = only this class uses them)
    // =============================================================================================

    /**
     * Does this booking count as real business? Pending, confirmed and completed do. Draft and cancelled do not.
     * Same rule as isRevenueBooking() in src/lib/domain.ts.
     */
    private function isLive(Booking $booking): bool
    {
        return in_array($booking->status, ['pending', 'confirmed', 'completed'], true);
    }

    /**
     * Warehouse status of one item: 'out' (nothing left), 'low' (below the minimum) or 'in_stock'.
     * Same rule as stockStatus() in src/lib/domain.ts.
     */
    private function stockStatus(InventoryItem $item): string
    {
        if ($item->quantity <= 0) {
            return 'out';
        }

        return $item->quantity < $item->min_quantity ? 'low' : 'in_stock';
    }

    /**
     * One row per month of the year, for the income/expenses chart:
     *   ['month' => '2026-03', 'income' => ..., 'expenses' => ..., 'weddings' => ...]
     *
     * - income   : cash received on live bookings whose EVENT is in that month + external income dated in that month.
     *              (Cash is grouped by the event month, not by the day the payment arrived. Same as the prototype.)
     * - expenses : expenses dated in that month.
     * - weddings : number of live bookings in that month (every event type, the name is historical).
     *
     * @return Collection<int, array{month:string, income:float, expenses:float, weddings:int}>
     */
    private function monthlySeries(int $year, Collection $live, Collection $income, Collection $expenses): Collection
    {
        return collect(range(1, 12))->map(function (int $m) use ($year, $live, $income, $expenses) {
            $key = sprintf('%d-%02d', $year, $m); // '2026-03'

            $monthBookings = $live->filter(fn (Booking $b) => $b->event_date->format('Y-m') === $key);

            $cash = $monthBookings->sum(fn (Booking $b) => $b->received())
                + $income->filter(fn ($x) => $x->received_on->format('Y-m') === $key)->sum('amount');

            $spent = $expenses->filter(fn ($x) => $x->spent_on->format('Y-m') === $key)->sum('amount');

            return [
                'month' => $key,
                'income' => $this->money($cash),
                'expenses' => $this->money($spent),
                'weddings' => $monthBookings->count(),
            ];
        });
    }

    /**
     * Counts live bookings per event type for one year.
     * Example result: [['type' => 'Wedding', 'count' => 12], ['type' => 'Birthday', 'count' => 3]]
     *
     * @return array<int, array{type:string, count:int}>
     */
    private function eventDistribution(Collection $live, int $year): array
    {
        return $live
            ->filter(fn (Booking $b) => $b->event_date->year === $year)
            ->groupBy('event_type')
            ->map(fn (Collection $group, string $type) => ['type' => $type, 'count' => $group->count()])
            ->values()
            ->all();
    }

    /** Rounds money to 2 decimals so floating-point noise (0.1 + 0.2 = 0.30000000000000004) never reaches the screen. */
    private function money(float|int $value): float
    {
        return round((float) $value, 2);
    }
}