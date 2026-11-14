<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Services\StockService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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
        return $this->move($request, 'stockIn', ['supplier' => ['required', 'string', 'max:255']], [
            'unitCost' => ['required', 'numeric', 'min:0'],
        ]);
    }

    public function stockOut(Request $request)
    {
        return $this->move($request, 'stockOut', ['bookingId' => ['nullable', Tenancy::exists('bookings')]]);
    }

    /**
     * Keep the single-item API and accept one batch for the React warehouse forms.
     */
    private function move(Request $request, string $action, array $rules, array $lineRules = [])
    {
        $shared = $this->movementRules() + $rules;
        $lineRules = $this->lineRules() + $lineRules;

        if (! $request->has('lines')) {
            return $this->created($this->stock->{$action}($request->validate($shared + $lineRules)));
        }

        $rules = $shared + ['lines' => ['required', 'array', 'min:1'], 'lines.*' => ['required', 'array']];
        foreach ($lineRules as $field => $fieldRules) {
            $rules["lines.*.{$field}"] = $fieldRules;
        }
        $data = $request->validate($rules);
        $lines = array_map(fn (array $line) => array_intersect_key($line, $lineRules), $data['lines']);
        $common = array_intersect_key($data, $shared);
        $movements = DB::transaction(function () use ($lines, $common, $action) {
            return array_map(fn (array $line) => $this->stock->{$action}($common + $line), $lines);
        }, 3);

        return StockMovementResource::collection(collect($movements))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function spoilage(Request $request)
    {
        $data = $request->validate($this->movementRules() + $this->lineRules());

        return $this->created($this->stock->spoilage($data));
    }

    private function movementRules(): array
    {
        return [
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /** Fields for each item that is moved. */
    private function lineRules(): array
    {
        return [
            'itemId' => ['required', Tenancy::exists('inventory_items')],
            'quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    private function created(StockMovement $movement)
    {
        return (new StockMovementResource($movement))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
