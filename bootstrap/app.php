<?php

/*
|--------------------------------------------------------------------------
| bootstrap/app.php  -  the application's "wiring" file
|--------------------------------------------------------------------------
| This file runs on every request. It tells Laravel:
|   1. which route files exist,
|   2. which middleware shortcuts (aliases) we can use in routes,
|   3. how errors are turned into JSON for our React frontend.
|
| REPLACES the default file created by `composer create-project`.
| (The default one only knew about web routes; this API needs routes/api.php.)
*/

use App\Http\Middleware\EnsureHotelIsActive;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))

    // ---- 1. Route files --------------------------------------------------
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        // Every route inside routes/api.php automatically gets the "/api" prefix.
        // Example: Route::get('/bookings') inside api.php  ->  GET http://localhost:8000/api/bookings
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up', // simple "is the server alive?" URL
    )

    // ---- 2. Middleware aliases ------------------------------------------
    // A middleware is a "gatekeeper" that runs BEFORE the controller.
    // Giving it a short name lets us write ->middleware('role:hotel_owner') in routes/api.php.
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,                 // checks the user's role
            'hotel.active' => EnsureHotelIsActive::class, // blocks suspended hotels
        ]);
    })

    // ---- 3. Error handling -----------------------------------------------
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always answer API URLs with JSON (never an HTML error page),
        // because our React app expects JSON.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // "Record not found" -> HTTP 404 with a clear message.
        //
        // SECURITY NOTE (multi-tenancy): when a hotel owner asks for a record that belongs to ANOTHER hotel,
        // the tenant scope hides it, so Laravel behaves as if it does not exist and we answer 404
        // (not 403). That way an attacker cannot even learn that the other record exists.
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Record not found.', 'code' => 'not_found'], 404);
            }
        });
    })
    ->create();
