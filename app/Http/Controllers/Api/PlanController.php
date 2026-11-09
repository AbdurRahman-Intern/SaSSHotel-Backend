<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;

/** PlanController  -  GET /api/plans. Read-only list used by the hotel create/edit form. */
class PlanController extends Controller
{
    public function index()
    {
        return PlanResource::collection(Plan::orderBy('price_monthly')->get());
    }

    // TODO (Super Admin "Plans" page): add store/update/destroy if plans should be editable.
}
