<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * ReportController  -  STUB for the Reports page.
 *
 * The React Reports page currently calculates its numbers in the browser from the lists it already loaded.
 * When data grows, move the heavy calculations here (the database is better at SUM/GROUP BY).
 *
 * TODO (suggested order):
 *   1. GET /api/reports/profit-loss?from=&to=   income - expenses by month
 *   2. GET /api/reports/bookings?from=&to=      count + revenue by event type
 *   3. GET /api/reports/inventory               stock value + spoilage by category
 *   4. CSV/PDF export (maatwebsite/excel or barryvdh/laravel-dompdf)
 */
class ReportController extends Controller
{
    public function profitLoss(Request $request)
    {
        return response()->json(['message' => 'Not implemented yet. See TODO in ReportController.'], 501);
    }
}
