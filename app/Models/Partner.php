<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Partner  -  a business partner with an account (contributions, profit shares, withdrawals). */
class Partner extends Model
{
    use HasUlids, BelongsToTenant;

    protected $fillable = ['full_name', 'phone', 'email', 'address', 'investment', 'share_percent', 'start_date', 'status', 'notes'];

    protected function casts(): array
    {
        return ['investment' => 'float', 'share_percent' => 'float', 'start_date' => 'date'];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PartnerTransaction::class);
    }
}
