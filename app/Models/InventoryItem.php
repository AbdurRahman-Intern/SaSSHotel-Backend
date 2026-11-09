<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** InventoryItem  -  a warehouse item with its CURRENT quantity (changed only by StockService). */
class InventoryItem extends Model
{
    use HasUlids, BelongsToTenant;

    // quantity / spoiled_quantity are NOT fillable: only StockService may change them.
    protected $fillable = ['name', 'category', 'unit', 'min_quantity', 'unit_cost'];

    protected function casts(): array
    {
        return [
            'quantity' => 'float', 'min_quantity' => 'float',
            'unit_cost' => 'float', 'spoiled_quantity' => 'float',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'item_id');
    }
}
