<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** ExternalIncome  -  income that is not a booking (parking, recycling ...). */
class ExternalIncome extends Model
{
    use HasUlids, BelongsToTenant;

    protected $table = 'external_income'; // Laravel would guess "external_incomes"
    protected $fillable = ['received_on', 'source', 'description', 'amount', 'received_by', 'notes'];

    protected function casts(): array
    {
        return ['received_on' => 'date', 'amount' => 'float'];
    }
}
