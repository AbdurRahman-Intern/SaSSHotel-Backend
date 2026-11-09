<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * AuthController  -  login / who am I / logout, using Sanctum bearer tokens.
 *
 * FLOW: React POSTs email+password -> we return { token, user } -> React stores the token and sends
 * "Authorization: Bearer <token>" on every later request (see src/lib/axios.ts).
 */
class AuthController extends Controller
{
    /** POST /api/auth/login  (public) */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Same message for "no such user" and "wrong password" so attackers cannot discover emails.
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid email or password.']);
        }

        // Suspended hotel owners cannot log in at all.
        if ($user->tenant_id && $user->hotel?->status === 'suspended') {
            return response()->json([
                'message' => 'This hotel account is suspended. Contact the platform administrator.',
                'code' => 'hotel_suspended',
            ], 403);
        }

        return response()->json([
            'token' => $user->createToken('react-app')->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    /** GET /api/auth/me */
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    /** POST /api/auth/logout  -> deletes only the token used for this request */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
