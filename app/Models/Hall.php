<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Hall  -  a wedding hall of one hotel.
 *
 * EVERY tenant model in this folder looks like this one:
 *   use HasUlids;         -> string ids
 *   use BelongsToTenant;  -> hotel isolation (read + write), see Concerns/BelongsToTenant.php
 *   $fillable             -> the columns a controller may save. `tenant_id` is NOT listed on purpose.
 *   casts()               -> turn database strings into PHP types (decimal -> float, date -> Carbon)
 */
class Hall extends Model
{
    use HasUlids, BelongsToTenant;

    protected $fillable = ['name', 'capacity'];

    protected function casts(): array
    {
        return ['capacity' => 'integer'];
    }
}
