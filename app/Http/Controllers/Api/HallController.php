<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\HallResource;
use App\Models\Hall;
use Illuminate\Validation\Rule;

/**
 * HallController  -  /api/halls. THE SIMPLEST EXAMPLE: copy this file to add a new simple module.
 * All five actions come from TenantCrudController; we only describe the data.
 */
class HallController extends TenantCrudController
{
    protected function modelClass(): string { return Hall::class; }
    protected function resourceClass(): string { return HallResource::class; }
    protected function orderBy(): array { return ['name', 'asc']; }

    protected function rules(?string $id): array
    {
        return [
            // Hall names are unique PER HOTEL. ignore($id) lets an update keep its own name.
            'name' => ['required', 'string', 'max:255',
                Rule::unique('halls', 'name')->where('tenant_id', \App\Support\Tenancy::id())->ignore($id)],
            'capacity' => ['required', 'integer', 'min:1'],
        ];
    }
}
