<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureHotelIsActive  -  blocks hotel owners whose hotel was SUSPENDED by the Super Admin.
 *
 * Why not only check at login?
 *   A hotel can be suspended while its owner is already logged in (the token is still valid).
 *   Checking on every request makes the suspension take effect immediately.
 *
 * Super admins have no hotel (tenant_id = null), so they pass straight through.
 * Alias: 'hotel.active'  (see bootstrap/app.php).
 */
class EnsureHotelIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // `$user->hotel` is a relationship defined in App\Models\User.
        if ($user && $user->tenant_id !== null && $user->hotel?->status === 'suspended') {
            return response()->json([
                'message' => 'This hotel account is suspended. Contact the platform administrator.',
                'code' => 'hotel_suspended', // the React app translates this code
            ], 403);
        }

        return $next($request);
    }
}
