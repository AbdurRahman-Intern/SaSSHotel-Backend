<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * PlanController  -  the subscription plans (Starter, Professional, Enterprise ...).
 *
 *   GET    /api/plans          any logged-in user  (dropdowns on the hotel form)   -> index()
 *   POST   /api/plans          Super Admin only                                    -> store()
 *   PATCH  /api/plans/{id}     Super Admin only                                    -> update()
 *   DELETE /api/plans/{id}     Super Admin only                                    -> destroy()
 *
 * Who may call which route is decided in routes/api.php (the `role:super_admin` group), not here.
 * Plans are global data (no tenant), so this controller does not use BelongsToTenant.
 *
 * TODO (when billing exists): enforce `max_users` when a hotel owner adds users, and charge `price_monthly`.
 */
class PlanController extends Controller
{
    /** GET /api/plans -> all plans, cheapest first. */
    public function index()
    {
        return PlanResource::collection(Plan::orderBy('price_monthly')->get());
    }

    /**
     * POST /api/plans  { name, priceMonthly, maxUsers }  ->  201 + the new plan
     *
     * The id is made from the name ("Gold Plus" -> "gold-plus"). The React app only ever reads it.
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $plan = Plan::create([
            'id' => $this->uniqueSlug($data['name']),
            'name' => $data['name'],
            'price_monthly' => $data['priceMonthly'],
            'max_users' => $data['maxUsers'],
        ]);

        ActivityLog::record("Plan created: {$plan->name}");

        return (new PlanResource($plan))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * PATCH /api/plans/{id}  -> change name / price / max users (only the fields that are sent).
     * The id never changes, because hotels point to it.
     */
    public function update(Request $request, string $id)
    {
        $plan = Plan::findOrFail($id);

        // "sometimes" = validate a field only if it was sent, so a PATCH may change just the price.
        $rules = collect($this->rules($id))
            ->map(fn ($r) => array_merge(['sometimes'], array_values(array_diff((array) $r, ['required']))))
            ->all();
        $data = $request->validate($rules);

        $changes = [];
        if (array_key_exists('name', $data)) {
            $changes['name'] = $data['name'];
        }
        if (array_key_exists('priceMonthly', $data)) {
            $changes['price_monthly'] = $data['priceMonthly'];
        }
        if (array_key_exists('maxUsers', $data)) {
            $changes['max_users'] = $data['maxUsers'];
        }
        $plan->update($changes);

        ActivityLog::record("Plan updated: {$plan->name}");

        return new PlanResource($plan);
    }

    /**
     * DELETE /api/plans/{id}  ->  204
     *
     * Refused (HTTP 422) while a hotel uses the plan or while it is the default plan in the platform settings.
     * Without this check MySQL would stop the delete with an ugly 500 error (foreign key from hotels.plan_id).
     */
    public function destroy(string $id)
    {
        $plan = Plan::findOrFail($id);

        $hotelCount = $plan->hotels()->count();
        if ($hotelCount > 0) {
            throw ValidationException::withMessages([
                'plan' => "This plan is used by {$hotelCount} hotel(s). Move them to another plan first.",
            ]);
        }

        if (PlatformSetting::all_()['defaultPlanId'] === $plan->id) {
            throw ValidationException::withMessages([
                'plan' => 'This plan is the default plan in the platform settings. Choose another default first.',
            ]);
        }

        $plan->delete();
        ActivityLog::record("Plan deleted: {$plan->name}");

        return response()->noContent();
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Validation rules, keyed by the camelCase names the React form sends.
     *
     * @param  string|null  $ignoreId  the plan being edited, so it may keep its own name
     */
    private function rules(?string $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('plans', 'name')->ignore($ignoreId, 'id')],
            'priceMonthly' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'maxUsers' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /**
     * "Gold Plus" -> "gold-plus". If that id exists already, add -2, -3 ... until it is free.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'plan';
        $slug = $base;
        $counter = 2;

        while (Plan::whereKey($slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
