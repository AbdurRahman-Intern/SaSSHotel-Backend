<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExternalIncome;
use App\Models\InventoryItem;
use App\Models\Loan;
use App\Models\SalaryPayment;

/**
 * DashboardService  -  calculates the numbers of the hotel owner dashboard.
 *
 * Output shape = DashboardStatistics in src/types/index.ts.
 *
 * INCOME vs EXPENSE RULES (important for correct profit):
 *   income    = cash RECEIVED on bookings (advance + payments) + external income
 *   expenses  = expenses table + salary payments that are 'paid'
 *   NOT counted: partner withdrawals, loan repayments (they are not operating costs)
 *
 * TODO (beginner exercise): the first version below fills the main fields. Compare with
 * `DashboardStatistics` in the React project and add any missing field one by one
 * (monthly[], eventDistribution, pendingPayments, upcomingGuests ...). Each is a small query.
 */
class DashboardService
{
    public function build(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $yearStart = now()->startOfYear()->toDateString();

        $bookings = Booking::with('payments')->where('status', '!=', 'cancelled')->get();

        // Cash received inside a date window (advance counted on the booking's creation date).
        $received = function (string $from) use ($bookings): float {
            $sum = 0.0;
            foreach ($bookings as $b) {
                if ($b->created_at->toDateString() >= $from) {
                    $sum += $b->advance_paid;
                }
                $sum += (float) $b->payments->where('paid_on', '>=', $from)->sum('amount');
            }

            return $sum;
        };

        $monthIncome = $received($monthStart) + (float) ExternalIncome::where('received_on', '>=', $monthStart)->sum('amount');
        $yearIncome = $received($yearStart) + (float) ExternalIncome::where('received_on', '>=', $yearStart)->sum('amount');

        $monthExpenses = (float) Expense::where('spent_on', '>=', $monthStart)->sum('amount')
            + (float) SalaryPayment::where('status', 'paid')->where('paid_date', '>=', $monthStart)->get()->sum(fn ($s) => $s->net());
        $yearExpenses = (float) Expense::where('spent_on', '>=', $yearStart)->sum('amount')
            + (float) SalaryPayment::where('status', 'paid')->where('paid_date', '>=', $yearStart)->get()->sum(fn ($s) => $s->net());

        $loans = Loan::with('repayments')->get();
        $items = InventoryItem::all();

        return [
            'dailyNetProfit' => 0, // TODO: today's received cash minus today's expenses
            'monthIncome' => $monthIncome,
            'yearIncome' => $yearIncome,
            'monthExpenses' => $monthExpenses,
            'yearExpenses' => $yearExpenses,
            'monthWeddings' => $bookings->filter(fn ($b) => $b->event_date->toDateString() >= $monthStart && $b->event_type === 'Wedding')->count(),
            'yearWeddings' => $bookings->filter(fn ($b) => $b->event_date->toDateString() >= $yearStart && $b->event_type === 'Wedding')->count(),
            'totalLoanGiven' => $loans->where('type', 'lent')->sum(fn ($l) => $l->outstanding()),
            'totalBorrowed' => $loans->where('type', 'borrowed')->sum(fn ($l) => $l->outstanding()),
            'stockValue' => $items->sum(fn ($i) => $i->quantity * $i->unit_cost),
            'totalEmployees' => Employee::where('status', 'active')->count(),
            'salaryPaidThisMonth' => SalaryPayment::where('month', now()->format('Y-m'))->where('status', 'paid')->get()->sum(fn ($s) => $s->net()),
            'monthly' => [],            // TODO: 12 rows { month, income, expenses } for the chart
            'recentBookings' => [],     // TODO: latest 5 bookings (use BookingResource)
            'lowStock' => $items->filter(fn ($i) => $i->quantity < $i->min_quantity)->values()->map->name,
            'upcoming' => [],           // TODO: next confirmed bookings from $today
            'eventDistribution' => [],  // TODO: count of bookings per event_type
            'pendingPayments' => 0,     // TODO: SUM of remaining() for non-cancelled bookings
            'pendingBookings' => $bookings->where('status', 'pending')->count(),
            'upcomingGuests' => 0,      // TODO: SUM guest_count of future bookings
            'todayBookings' => $bookings->filter(fn ($b) => $b->event_date->toDateString() === $today)->count(),
        ];
    }
}
