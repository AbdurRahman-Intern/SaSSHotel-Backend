<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Plan  -  a subscription plan (starter / professional / enterprise).
 * Global data (no tenant). The id is a readable string, so auto-increment is switched off.
 */
class Plan extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'price_monthly', 'max_users'];

    protected function casts(): array
    {
        return ['price_monthly' => 'float', 'max_users' => 'integer'];
    }
}
