<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

/**
 * DashboardController  -  GET /api/dashboard?year=2026   (hotel owner only, see routes/api.php)
 *
 * The controller stays tiny on purpose: it only reads the request and hands the work to DashboardService,
 * where all the calculations (and their explanations) live.
 * `__invoke` = "this controller has exactly one action", so the route is written Route::get('/dashboard', DashboardController::class).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard)
    {
        // `year` is optional. When it is missing we use the current year.
        $data = $request->validate(['year' => ['nullable', 'integer', 'between:2000,2100']]);

        return response()->json($dashboard->build($data['year'] ?? null));
    }
}