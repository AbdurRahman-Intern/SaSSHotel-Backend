<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Booking  -  an event reservation.
 *
 * FORMULAS (must match src/lib/domain.ts in the React app):
 *   total     = guest_count x food_price_per_person + photography + decoration + service_charge + other_charges - discount
 *   received  = advance_paid + SUM(payments)
 *   remaining = total - received
 */
class Booking extends Model
{
    use HasUlids, BelongsToTenant;

    // `reference` is NOT fillable: the server generates it (BookingController / BookingService).
    protected $fillable = [
        'client_name', 'client_phone', 'event_type', 'event_date', 'shift', 'guest_count', 'hall_name',
        'menu_id', 'food_price_per_person', 'photography_price', 'decoration_price', 'service_charge',
        'other_charges', 'discount', 'advance_paid', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'guest_count' => 'integer',
            'food_price_per_person' => 'float', 'photography_price' => 'float', 'decoration_price' => 'float',
            'service_charge' => 'float', 'other_charges' => 'float', 'discount' => 'float', 'advance_paid' => 'float',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /** Estimated total of the event. */
    public function total(): float
    {
        return $this->guest_count * $this->food_price_per_person
            + $this->photography_price + $this->decoration_price
            + $this->service_charge + $this->other_charges - $this->discount;
    }

    /** Cash actually received so far. Needs the `payments` relation (eager-load it to avoid N+1 queries). */
    public function received(): float
    {
        return $this->advance_paid + (float) $this->payments->sum('amount');
    }

    public function remaining(): float
    {
        return $this->total() - $this->received();
    }
}
