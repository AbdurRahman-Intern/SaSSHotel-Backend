<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\RequisitionService;

/**
 * RequisitionController  -  the hawala page of one booking.
 *   GET  /api/bookings/{id}/requisition        -> calculated lines + whether stock is enough (changes nothing)
 *   POST /api/bookings/{id}/requisition/issue  -> really takes the goods out of the warehouse
 */
class RequisitionController extends Controller
{
    public function __construct(private RequisitionService $requisitions) {}

    public function preview(string $id)
    {
        return response()->json($this->requisitions->preview(Booking::findOrFail($id)));
    }

    public function issue(string $id)
    {
        $count = $this->requisitions->issue(Booking::findOrFail($id));
        // TODO: prevent issuing the same booking twice (e.g. check for existing 'out' movements with this booking_id).

        return response()->json(['issued' => $count]);
    }
}
