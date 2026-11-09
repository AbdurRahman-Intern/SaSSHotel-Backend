<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider  -  runs once when Laravel boots. Replaces the default file.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nothing to register yet. (Bind your own services here later.)
    }

    public function boot(): void
    {
        // By default Laravel wraps resources like {"data": {...}}.
        // The React app expects the plain object/array (like JSON Server), so we switch wrapping off.
        JsonResource::withoutWrapping();
    }
}
