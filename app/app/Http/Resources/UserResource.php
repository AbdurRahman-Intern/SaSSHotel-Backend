<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API RESOURCES 101 (applies to every *Resource.php file)
 * A resource decides EXACTLY what JSON the frontend receives for one model:
 *   - snake_case database columns  ->  camelCase keys (React style)
 *   - decimals                     ->  numbers
 *   - dates                        ->  'YYYY-MM-DD'
 * Never return a model directly from a controller; hidden columns could leak (password!).
 * Shape must match src/types/index.ts in the React project.
 *
 * THIS FILE: User { id, name, email, role, tenantId }
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,           // users.id is a number in MySQL; React wants a string
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'tenantId' => $this->tenant_id,
        ];
    }
}
