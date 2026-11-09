<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PartnerTransaction  -  contribution / profit_share / withdrawal. A withdrawal is NOT an expense! */
class PartnerTransaction extends Model
{
    use HasUlids, BelongsToTenant;

    protected $fillable = ['partner_id', 'type', 'amount', 'occurred_on', 'note'];

    protected function casts(): array
    {
        return ['amount' => 'float', 'occurred_on' => 'date'];
    }
}
