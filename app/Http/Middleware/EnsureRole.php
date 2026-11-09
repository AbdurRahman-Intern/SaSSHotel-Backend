<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureRole  -  "only users with this role may pass".
 *
 * USED IN routes/api.php (the alias "role" is registered in bootstrap/app.php):
 *
 *     Route::middleware('role:super_admin')->group(function () { ... });
 *     Route::middleware('role:hotel_owner')->group(function () { ... });
 *
 * The text after the colon ("super_admin") is passed to handle() as $role.
 *
 * WHY THE BACKEND MUST DO THIS
 *   The React app already hides pages from the wrong role, but that is only for convenience:
 *   anyone can call the API directly with curl/Postman. THIS middleware is the real lock.
 *
 * Runs AFTER `auth:sanctum`, so $request->user() is already the logged-in user.
 */
class EnsureRole
{
    /**
     * @param  Closure(Request): Response  $next  the next step (usually the controller)
     * @param  string  $role  'super_admin' or 'hotel_owner'
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        // 401 = "who are you?"  (no valid token)
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.', 'code' => 'unauthenticated'], 401);
        }

        // 403 = "I know who you are, but you may not do this."
        if ($user->role !== $role) {
            return response()->json(['message' => 'You do not have permission to access this resource.', 'code' => 'forbidden'], 403);
        }

        return $next($request); // allowed -> continue to the controller
    }
}
