<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rule;

/**
 * Tenancy  -  small helpers for "which hotel am I working for?".
 *
 * Keeping these in ONE place means controllers never touch tenant ids directly.
 */
class Tenancy
{
    /**
     * The logged-in user's hotel id (null for the Super Admin).
     * ALWAYS take the tenant from here, never from request input.
     */
    public static function id(): ?string
    {
        return Auth::user()?->tenant_id;
    }

    /**
     * Validation rule: "this id must exist AND belong to MY hotel".
     *
     * WHY NOT the plain 'exists:employees,id' string rule?
     *   The plain rule runs raw SQL, which IGNORES our tenant scope. A hotel owner could then submit
     *   another hotel's employee id and the validator would accept it. This helper adds the missing
     *   `tenant_id` condition.
     *
     * Usage inside a FormRequest / validate() call:
     *   'employeeId' => ['required', Tenancy::exists('employees')],
     *
     * @param  string  $table   table name, e.g. 'employees'
     * @param  string  $column  column to match, default 'id'
     */
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('tenant_id', self::id());
    }
}
