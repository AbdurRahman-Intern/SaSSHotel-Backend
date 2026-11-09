<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
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
    /** GET /api/platform/stats */
    public function stats()
    {
        $hotels = Hotel::with('plan')->get();

        return response()->json([
            'totalHotels' => $hotels->count(),
            'activeHotels' => $hotels->where('status', 'active')->count(),
            'suspendedHotels' => $hotels->where('status', 'suspended')->count(),
            'totalUsers' => User::count(),
            // Monthly recurring revenue = sum of the plan price of every ACTIVE hotel.
            'monthlyRevenue' => $hotels->where('status', 'active')->sum(fn ($h) => $h->plan?->price_monthly ?? 0),
            // TODO: compare with PlatformStats in the React types and add any missing field.
        ]);
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
