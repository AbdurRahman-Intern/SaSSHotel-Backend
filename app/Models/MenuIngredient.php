<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MenuIngredient  -  one recipe line: "X per person of item Y".
 * No tenant trait: it is only ever reached THROUGH a Menu, which is already tenant-scoped.
 */
class MenuIngredient extends Model
{
    protected $fillable = ['menu_id', 'inventory_item_id', 'per_person'];

    protected function casts(): array
    {
        return ['per_person' => 'float'];
    }
}
