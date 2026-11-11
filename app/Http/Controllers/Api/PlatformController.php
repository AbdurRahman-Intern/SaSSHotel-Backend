<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\HotelResource;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\Hotel;
use App\Models\Plan;
use App\Models\User;

/**
 * PlatformController  -  Super Admin dashboard data: statistics, activity feed, all users.
 * Output shape of stats() = PlatformStats in src/types/index.ts.
 */
class PlatformController extends Controller
{
    /**
     * GET /api/platform/stats  -  everything the Super Admin dashboard shows.
     * The keys match `PlatformStats` in src/types/index.ts, so the React page can use the JSON as it is.
     */
    public function stats()
    {
        $hotels = Hotel::with('plan')->get();
        $plans = Plan::orderBy('price_monthly')->get();

        return response()->json([
            'totalHotels' => $hotels->count(),
            'activeHotels' => $hotels->where('status', 'active')->count(),
            'suspendedHotels' => $hotels->where('status', 'suspended')->count(),
            'totalUsers' => User::count(),

            // The 5 most recently registered hotels. ->resolve() turns the resource into a plain array.
            'recentHotels' => HotelResource::collection($hotels->sortByDesc('registered_at')->take(5)->values())->resolve(),

            // Line chart: how many hotels existed at the END of each month, from January to the current month.
            // "Cumulative" = hotels registered in earlier months (or years) are counted too.
            'growth' => $this->growth($hotels),

            // Doughnut chart: how many hotels use each plan (plans with 0 hotels are listed too).
            'planDistribution' => $plans->map(fn (Plan $plan) => [
                'plan' => $plan->name,
                'count' => $hotels->where('plan_id', $plan->id)->count(),
            ])->values(),

            // The 6 newest events of the activity feed.
            'activity' => ActivityLogResource::collection(ActivityLog::orderByDesc('created_at')->limit(6)->get())->resolve(),

            // Extra (not in the React type yet): monthly recurring revenue = plan prices of all ACTIVE hotels.
            'monthlyRevenue' => $hotels->where('status', 'active')->sum(fn (Hotel $h) => $h->plan?->price_monthly ?? 0),
        ]);
    }

    /**
     * Cumulative number of hotels at the end of each month of this year, up to the current month.
     * Example: [['month' => '2026-01', 'hotels' => 2], ['month' => '2026-02', 'hotels' => 3], ...]
     */
    private function growth($hotels): array
    {
        $year = now()->year;

        return collect(range(1, now()->month))->map(function (int $month) use ($hotels, $year) {
            $endOfMonth = now()->setDate($year, $month, 1)->endOfMonth();

            return [
                'month' => sprintf('%d-%02d', $year, $month),
                'hotels' => $hotels->filter(fn (Hotel $h) => $h->registered_at->lte($endOfMonth))->count(),
            ];
        })->all();
    }

    /** GET /api/platform/activity  -> latest 10 events */
    public function activity()
    {
        return ActivityLogResource::collection(ActivityLog::orderByDesc('created_at')->limit(10)->get());
    }

    /** GET /api/users  -> every user of the platform (Super Admin "Users" page) */
    public function users()
    {
        return UserResource::collection(User::orderBy('name')->get());
    }
}