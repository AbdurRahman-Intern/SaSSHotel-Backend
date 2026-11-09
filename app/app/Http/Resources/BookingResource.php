<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Booking { id, reference, clientName, ... payments:[{date, amount}] ... }
 * Totals are NOT sent: the React app calculates them with src/lib/domain.ts (same formulas as Booking model).
 * TODO (optional): also send total/received/remaining if you want the server to be the single source of truth.
 */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'clientName' => $this->client_name,
            'clientPhone' => $this->client_phone,
            'eventType' => $this->event_type,
            'eventDate' => $this->event_date?->toDateString(),
            'shift' => $this->shift,
            'guestCount' => $this->guest_count,
            'hallName' => $this->hall_name,
            'menuId' => $this->menu_id,
            'foodPricePerPerson' => $this->food_price_per_person,
            'photographyPrice' => $this->photography_price,
            'decorationPrice' => $this->decoration_price,
            'serviceCharge' => $this->service_charge,
            'otherCharges' => $this->other_charges,
            'discount' => $this->discount,
            'advancePaid' => $this->advance_paid,
            'payments' => $this->payments->map(fn ($p) => ['date' => $p->paid_on->toDateString(), 'amount' => $p->amount])->values(),
            'status' => $this->status,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toDateString(),
        ];
    }
}
