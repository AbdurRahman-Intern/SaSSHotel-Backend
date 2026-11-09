<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** BookingService  -  booking rules that need more than one query. */
class BookingService
{
    /**
     * Is the hall free for this date + shift?  Throws a 422 if another ACTIVE booking has it.
     * Cancelled bookings do not block the hall.
     *
     * @param  string|null  $ignoreId  the booking being edited (so it doesn't clash with itself)
     */
    public function assertHallFree(string $hallName, string $date, string $shift, ?string $ignoreId = null): void
    {
        $clash = Booking::query()
            ->where('hall_name', $hallName)
            ->whereDate('event_date', $date)
            ->where('shift', $shift)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($clash) {
            throw ValidationException::withMessages([
                'hallName' => "The hall {$hallName} is already booked for that date and shift.",
            ]);
        }
    }

    /**
     * Next reference for this hotel, like BK-2026-001 (counter restarts every year).
     * Called inside a transaction by the controller so two requests cannot get the same number.
     * (The unique index on tenant_id+reference is the final safety net.)
     */
    public function nextReference(): string
    {
        $year = now()->year;
        $prefix = "BK-{$year}-";

        // Booking::query() is already limited to the logged-in hotel by the tenant scope.
        $last = Booking::query()
            ->where('reference', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('reference')
            ->value('reference');

        $number = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    /** Wrapper so controllers can write one line. */
    public function transaction(callable $callback)
    {
        return DB::transaction($callback);
    }
}
