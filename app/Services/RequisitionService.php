<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;

/**
 * RequisitionService  -  the warehouse "hawala" (requisition) for one booking.
 *
 *   required quantity of an ingredient = perPerson x guestCount
 *
 * preview() only calculates. issue() really takes the goods out of stock (through StockService).
 */
class RequisitionService
{
    public function __construct(private StockService $stock) {}

    /**
     * Calculate the lines for a booking. Used by the hawala page before the user confirms.
     *
     * @return array<int, array{itemId:string, name:string, unit:string, required:float, available:float, enough:bool}>
     */
    public function preview(Booking $booking): array
    {
        if (! $booking->menu_id) {
            return []; // no menu chosen -> nothing to issue
        }

        $menu = Menu::with('ingredients')->find($booking->menu_id);
        if (! $menu) {
            return [];
        }

        $items = \App\Models\InventoryItem::whereIn('id', $menu->ingredients->pluck('inventory_item_id'))->get()->keyBy('id');

        return $menu->ingredients->map(function ($ing) use ($booking, $items) {
            $item = $items[$ing->inventory_item_id] ?? null;
            $required = round($ing->per_person * $booking->guest_count, 2);

            return [
                'itemId' => $ing->inventory_item_id,
                'name' => $item?->name ?? 'Unknown',
                'unit' => $item?->unit ?? '',
                'required' => $required,
                'available' => $item?->quantity ?? 0,
                'enough' => $item !== null && $item->quantity >= $required,
            ];
        })->values()->all();
    }

    /**
     * Issue every line. All-or-nothing: if ONE item is short, a 422 is thrown and NOTHING is deducted.
     *
     * @return int number of movements created
     */
    public function issue(Booking $booking): int
    {
        $lines = $this->preview($booking);

        return DB::transaction(function () use ($booking, $lines) {
            foreach ($lines as $line) {
                $this->stock->stockOut([
                    'itemId' => $line['itemId'],
                    'quantity' => $line['required'],
                    'date' => now()->toDateString(),
                    'reference' => "{$booking->event_type} - {$booking->client_name}",
                    'bookingId' => $booking->id,
                ]);
            }

            return count($lines);
        });
    }
}
