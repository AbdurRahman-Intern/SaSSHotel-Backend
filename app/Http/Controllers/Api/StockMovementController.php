<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Services\StockService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * StockMovementController  -  the warehouse ledger.
 *   GET  /api/stock-movements?type=in|out|spoilage&itemId=...
 *   POST /api/stock-movements/in        (purchase)
 *   POST /api/stock-movements/out       (issue goods)
 *   POST /api/stock-movements/spoilage  (damaged goods)
 * Quantities are changed by StockService (transaction + lock + "not enough stock" check).
 * There is NO update/delete: wrong entry? Add a correcting movement instead.
 */
class StockMovementController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function index(Request $request)
    {
        $query = StockMovement::query()->orderByDesc('moved_on')->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }
        if ($request->filled('itemId')) {
            $query->where('item_id', $request->query('itemId'));
        }

        return StockMovementResource::collection($query->get());
    }

    public function stockIn(Request $request)
    {
        $data = $request->validate($this->rules() + [
            'unitCost' => ['required', 'numeric', 'min:0'],
            'supplier' => ['required', 'string', 'max:255'],
        ]);

        return $this->created($this->stock->stockIn($data));
    }

    public function stockOut(Request $request)
    {
        $data = $request->validate($this->rules() + [
            'bookingId' => ['nullable', Tenancy::exists('bookings')],
        ]);

        return $this->created($this->stock->stockOut($data));
    }

    public function spoilage(Request $request)
    {
        return $this->created($this->stock->spoilage($request->validate($this->rules())));
    }

    /** Rules shared by the three POST endpoints. */
    private function rules(): array
    {
        return [
            'itemId' => ['required', Tenancy::exists('inventory_items')],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function created(StockMovement $movement)
    {
        return (new StockMovementResource($movement))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
