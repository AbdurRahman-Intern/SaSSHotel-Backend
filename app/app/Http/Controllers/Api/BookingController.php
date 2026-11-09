<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Hall;
use App\Services\BookingService;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * BookingController  -  /api/bookings (+ payments, cancel).
 *
 * Extra rules compared with the plain CRUD:
 *   - the server generates `reference` (BK-2026-001)
 *   - a hall cannot be double-booked for the same date + shift
 *   - creating/updating a booking NEVER touches warehouse stock
 */
class BookingController extends TenantCrudController
{
    public function __construct(private BookingService $bookings) {}

    protected function modelClass(): string { return Booking::class; }
    protected function resourceClass(): string { return BookingResource::class; }
    protected function with(): array { return ['payments']; }
    protected function filterable(): array { return ['status', 'eventType', 'shift', 'hallName', 'eventDate']; }
    protected function orderBy(): array { return ['event_date', 'desc']; }

    protected function rules(?string $id): array
    {
        return [
            'clientName' => ['required', 'string', 'max:255'],
            'clientPhone' => ['required', 'string', 'max:40'],
            'eventType' => ['required', Rule::in(['Wedding', 'Engagement', 'Reception', 'Birthday', 'Corporate', 'Other'])],
            'eventDate' => ['required', 'date'],
            'shift' => ['required', Rule::in(['Day', 'Night'])],
            'guestCount' => ['required', 'integer', 'min:1'],
            // The hall must be one of MY hotel's halls.
            'hallName' => ['required', Rule::exists('halls', 'name')->where('tenant_id', Tenancy::id())],
            'menuId' => ['nullable', Tenancy::exists('menus')],
            'foodPricePerPerson' => ['required', 'numeric', 'min:0'],
            'photographyPrice' => ['numeric', 'min:0'],
            'decorationPrice' => ['numeric', 'min:0'],
            'serviceCharge' => ['numeric', 'min:0'],
            'otherCharges' => ['numeric', 'min:0'],
            'discount' => ['numeric', 'min:0'],
            'advancePaid' => ['numeric', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'pending', 'confirmed', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
        // TODO: reject `discount` bigger than the total and `advancePaid` bigger than the total.
    }

    /** POST /api/bookings */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(null));

        // Transaction: the reference lookup and the insert happen together.
        $booking = $this->bookings->transaction(function () use ($validated) {
            $this->bookings->assertHallFree($validated['hallName'], $validated['eventDate'], $validated['shift']);

            $booking = new Booking($this->toColumns($validated));
            $booking->reference = $this->bookings->nextReference(); // not fillable on purpose
            $booking->save();

            return $booking;
        });

        return (new BookingResource($booking->fresh('payments')))->response()->setStatusCode(201);
    }

    /** PATCH /api/bookings/{id} - re-checks the hall when date/shift/hall changed. */
    public function update(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $hall = $request->input('hallName', $booking->hall_name);
        $date = $request->input('eventDate', $booking->event_date->toDateString());
        $shift = $request->input('shift', $booking->shift);
        $this->bookings->assertHallFree($hall, $date, $shift, $booking->id);

        return parent::update($request, $id);
    }

    /** POST /api/bookings/{id}/payments  { date, amount } -> returns the updated booking */
    public function addPayment(Request $request, string $id)
    {
        $booking = Booking::with('payments')->findOrFail($id);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);
        // TODO: refuse a payment that is larger than $booking->remaining() (ask the client first).

        BookingPayment::create(['booking_id' => $booking->id, 'paid_on' => $data['date'], 'amount' => $data['amount']]);

        return new BookingResource($booking->fresh('payments'));
    }

    /** POST /api/bookings/{id}/cancel - sets status to cancelled (the hall becomes free again). */
    public function cancel(string $id)
    {
        $booking = Booking::with('payments')->findOrFail($id);
        $booking->update(['status' => 'cancelled']);
        // TODO: decide the refund policy for money already received.

        return new BookingResource($booking);
    }
}
