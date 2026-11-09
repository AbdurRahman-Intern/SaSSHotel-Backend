<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * BelongsToTenant  -  THE most important security file in this project.
 *
 * WHAT IS A "TENANT"?
 *   Every hotel is a tenant. All hotels share ONE database, so every business table
 *   (bookings, employees, expenses ...) has a `tenant_id` column that stores the hotel's id.
 *   Hotel A must NEVER see Hotel B's rows.
 *
 * HOW THIS TRAIT PROTECTS US
 *   Put `use BelongsToTenant;` inside a model and two things happen automatically:
 *
 *   1. READ  : a "global scope" adds `WHERE tenant_id = <logged-in user's hotel>` to EVERY query
 *              on that model (Booking::all(), Booking::find($id), Booking::where(...) ...).
 *              A record of another hotel is simply invisible -> find() returns null -> HTTP 404.
 *
 *   2. WRITE : when a row is created, `tenant_id` is filled from the logged-in user.
 *              We NEVER read tenant_id from the request body, so a client cannot choose a hotel.
 *
 * FAIL CLOSED
 *   If nobody is logged in, or the user has no hotel (the platform super admin), the scope returns
 *   NOTHING (`WHERE 1 = 0`) instead of everything. Forgetting a check therefore leaks no data.
 *
 * LIMITS (read this!)
 *   - Global scopes do NOT apply to raw SQL (`DB::table(...)`, `DB::select(...)`). Avoid raw queries
 *     on tenant tables, or add the tenant condition by hand.
 *   - Validation rules like `exists:employees,id` use raw SQL, so they ignore this scope. Always use
 *     `Rule::exists('employees', 'id')->where('tenant_id', ...)` (see App\Support\Tenancy::exists()).
 *   - Seeders and console commands have no logged-in user. Create rows with `Model::withoutTenant()`
 *     or set tenant_id yourself (see DatabaseSeeder for an example).
 */
trait BelongsToTenant
{
    /**
     * Laravel automatically calls "boot" + TraitName when a model starts.
     * So for this trait, Laravel calls bootBelongsToTenant().
     */
    protected static function bootBelongsToTenant(): void
    {
        // ---- 1. READ protection -------------------------------------------
        static::addGlobalScope('tenant', function (Builder $query) {
            $user = Auth::user();

            if ($user instanceof User && $user->tenant_id !== null) {
                $query->where($query->getModel()->getTable() . '.tenant_id', $user->tenant_id);
            } else {
                // No logged-in hotel user => show nothing. (Fail closed.)
                $query->whereRaw('1 = 0');
            }
        });

        // ---- 2. WRITE protection ------------------------------------------
        static::creating(function (Model $model) {
            $user = Auth::user();

            // Seeders/commands may set tenant_id by hand; only fill it automatically for real users.
            if ($user instanceof User && $user->tenant_id !== null) {
                // Overwrite on purpose: even if a client sent a "tenant_id", we ignore it.
                $model->tenant_id = $user->tenant_id;
            }
        });
    }

    /**
     * Relationship: $booking->hotel  gives the Hotel this row belongs to.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Hotel::class, 'tenant_id');
    }

    /**
     * Use ONLY in seeders/console commands/tests (never in controllers):
     *   Booking::withoutTenant()->where('tenant_id', $hotelId)->get();
     * It removes the tenant filter so you can see every hotel's rows.
     */
    public static function withoutTenant(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
