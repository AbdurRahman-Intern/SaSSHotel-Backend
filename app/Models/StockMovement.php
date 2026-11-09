<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** StockMovement  -  one ledger row (in / out / spoilage). Created by StockService, never edited. */
class StockMovement extends Model
{
    use HasUlids, BelongsToTenant;

    protected $fillable = ['item_id', 'type', 'quantity', 'unit_cost', 'moved_on', 'supplier', 'reference', 'booking_id', 'notes'];

    protected function casts(): array
    {
        return ['quantity' => 'float', 'unit_cost' => 'float', 'moved_on' => 'date'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
