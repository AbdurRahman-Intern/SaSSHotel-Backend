<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;

/** DashboardController  -  GET /api/dashboard (hotel owner). The numbers are calculated in DashboardService. */
class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard)
    {
        return response()->json($dashboard->build());
    }
}
