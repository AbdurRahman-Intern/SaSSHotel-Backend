<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Menu  -  a food menu with a price per person and a recipe (ingredients). */
class Menu extends Model
{
    use HasUlids, BelongsToTenant;

    protected $fillable = ['name', 'price_per_person'];

    protected function casts(): array
    {
        return ['price_per_person' => 'float'];
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(MenuIngredient::class);
    }
}
