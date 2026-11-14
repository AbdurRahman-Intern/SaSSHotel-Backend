<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * TenantCrudController  -  the reusable "list / show / create / update / delete" controller.
 *
 * WHY IT EXISTS
 *   About 10 modules (halls, employees, expenses ...) need the same five actions. Instead of copy-pasting,
 *   each module controller EXTENDS this class and only describes what is different:
 *
 *       class HallController extends TenantCrudController {
 *           protected function modelClass(): string    { return Hall::class; }
 *           protected function resourceClass(): string { return HallResource::class; }
 *           protected function rules(?string $id): array { return ['name' => ['required','string']]; }
 *       }
 *
 *   If a module needs special behaviour (e.g. bookings) override one method (store, update ...).
 *
 * TENANT SAFETY
 *   Models use BelongsToTenant, so every query here is automatically limited to the logged-in hotel and
 *   a foreign id gives 404. This class never reads a tenant id.
 *
 * INPUT -> DATABASE NAMES
 *   The React app sends camelCase (clientName). validated() keys are converted to snake_case (client_name).
 */
abstract class TenantCrudController extends Controller
{
    /** The Eloquent model class, e.g. Hall::class */
    abstract protected function modelClass(): string;

    /** The API Resource class used for the JSON output, e.g. HallResource::class */
    abstract protected function resourceClass(): string;

    /**
     * Validation rules, keyed by the camelCase names the frontend sends.
     * @param  string|null  $id  the record id when updating (null when creating) - handy for unique rules
     */
    abstract protected function rules(?string $id): array;

    /** Relations to eager-load (avoids the "N+1 query" slowness). Override when the resource uses relations. */
    protected function with(): array
    {
        return [];
    }

    /** Query-string filters allowed on the list endpoint, e.g. ['status', 'category'] -> GET /employees?status=active */
    protected function filterable(): array
    {
        return [];
    }

    /** Default sort for the list endpoint: [column, direction] */
    protected function orderBy(): array
    {
        return ['created_at', 'desc'];
    }

    /** Hook: adjust validated data before saving (e.g. add computed fields). */
    protected function prepare(array $data, ?Model $existing = null): array
    {
        return $data;
    }

    /** Hook: runs after a record was created/updated, inside the request (e.g. sync child rows). */
    protected function saved(Model $model, array $validated): void
    {
        //
    }

    /** Starting query. Tenant scope is added automatically by the model. */
    protected function query(): Builder
    {
        return ($this->modelClass())::query()->with($this->with());
    }

    // ------------------------------------------------------------------ actions

    /** GET /resource  -> list */
    public function index(Request $request)
    {
        $query = $this->query();

        foreach ($this->filterable() as $field) {
            if ($request->filled($field)) {
                // camelCase query param -> snake_case column
                $query->where(Str::snake($field), $request->query($field));
            }
        }

        [$column, $direction] = $this->orderBy();
        // TODO (later): add pagination -> ->paginate(25) and tell the React app how to read it.
        $items = $query->orderBy($column, $direction)->get();

        return ($this->resourceClass())::collection($items);
    }

    /** GET /resource/{id} -> one record (404 if it belongs to another hotel) */
    public function show(string $id)
    {
        $model = $this->query()->findOrFail($id);

        return new ($this->resourceClass())($model);
    }

    /** POST /resource -> create (201) */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(null));
        $model = DB::transaction(function () use ($validated) {
            $model = ($this->modelClass())::create($this->prepare($this->toColumns($validated)));
            $this->saved($model, $validated);

            return $model;
        });

        return (new ($this->resourceClass())($model->fresh($this->with())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /** PATCH /resource/{id} -> update (only the fields that were sent) */
    public function update(Request $request, string $id)
    {
        $model = $this->query()->findOrFail($id);

        // "sometimes" = validate a field only if it was sent, so partial updates (PATCH) work.
        $rules = collect($this->rules($id))
            ->map(fn ($r) => array_merge(['sometimes'], array_values(array_filter((array) $r, fn ($x) => $x !== 'required'))))
            ->all();

        $validated = $request->validate($rules);
        $model->update($this->prepare($this->toColumns($validated), $model));
        $this->saved($model, $validated);

        return new ($this->resourceClass())($model->fresh($this->with()));
    }

    /** DELETE /resource/{id} -> 204 No Content */
    public function destroy(string $id): JsonResponse|Response
    {
        $this->query()->findOrFail($id)->delete();

        return response()->noContent();
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Convert ['clientName' => 'x'] into ['client_name' => 'x'] and drop nested arrays
     * (children such as `ingredients` are handled by saved()).
     */
    protected function toColumns(array $validated): array
    {
        $columns = [];
        foreach ($validated as $key => $value) {
            if (! is_array($value)) {
                $columns[Str::snake($key)] = $value;
            }
        }

        return $columns;
    }
}
